<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\BranchSaleStatus;
use App\Enums\ItemStageCode;
use App\Enums\MovementType;
use App\Models\BranchSale;
use App\Models\FinanceCategory;
use App\Models\FinanceIncome;
use App\Models\Item;
use App\Models\ItemStage;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class BranchSaleService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected StockBalanceService $stockBalanceService,
        protected StockMovementService $stockMovementService,
    ) {
    }

    public function syncTotals(BranchSale $sale): BranchSale
    {
        $sale->loadMissing('items');

        $subtotal = 0.0;
        $cogs = 0.0;

        foreach ($sale->items as $line) {
            $lineTotal = (float) $line->qty * (float) $line->unit_price;

            if ((float) $line->line_total !== $lineTotal) {
                $line->update(['line_total' => $lineTotal]);
            }

            $subtotal += $lineTotal;
            $cogs += (float) $line->cogs_total;
        }

        $totalAmount = max(0, $subtotal - (float) $sale->discount_amount + (float) $sale->tax_amount);
        $grossProfit = $totalAmount - $cogs;

        $sale->update([
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount,
            'cogs_amount' => $cogs,
            'gross_profit' => $grossProfit,
        ]);

        return $sale->fresh('items');
    }

    public function post(BranchSale $sale, User $actor): BranchSale
    {
        if ($sale->status !== BranchSaleStatus::Draft) {
            throw new InvalidArgumentException('Hanya nota draft yang dapat diposting.');
        }

        return DB::transaction(function () use ($sale, $actor) {
            $sale = $this->syncTotals($sale->fresh(['items.item.defaultStage']));

            if ($sale->items->isEmpty()) {
                throw new InvalidArgumentException('Nota tidak memiliki item.');
            }

            $branchStockStageId = ItemStage::query()
                ->where('code', ItemStageCode::BranchStock->value)
                ->value('id');

            $branchWarehouseId = Warehouse::query()
                ->where('branch_id', $sale->branch_id)
                ->orderByRaw("CASE WHEN location_type = 'branch' THEN 0 ELSE 1 END")
                ->value('id');

            $movementItems = [];
            $cogsAmount = 0.0;

            foreach ($sale->items as $line) {
                $stageId = $this->resolveSaleStageId($line->item, $branchStockStageId);

                $balance = $this->stockBalanceService->getOrCreate(
                    itemId: $line->item_id,
                    stageId: $stageId,
                    warehouseId: $branchWarehouseId,
                    branchId: $sale->branch_id,
                    stockBatchId: null,
                );

                $availableQty = (float) $balance->qty_on_hand;
                $requiredQty = (float) $line->qty;

                if ($availableQty < $requiredQty) {
                    throw new InvalidArgumentException(sprintf(
                        'Stok cabang untuk %s tidak cukup. Tersedia %.4f, diminta %.4f.',
                        $line->item?->name ?? ('Item #'.$line->item_id),
                        $availableQty,
                        $requiredQty,
                    ));
                }

                $cogsUnit = (float) $balance->avg_cost;
                $lineCogs = $requiredQty * $cogsUnit;
                $cogsAmount += $lineCogs;

                $line->update([
                    'cogs_unit' => $cogsUnit,
                    'cogs_total' => $lineCogs,
                ]);

                $movementItems[] = [
                    'item_id' => $line->item_id,
                    'unit_id' => $line->unit_id,
                    'direction' => 'out',
                    'qty' => $requiredQty,
                    'unit_cost' => $cogsUnit,
                    'from_stage_id' => $stageId,
                    'from_warehouse_id' => $branchWarehouseId,
                    'from_branch_id' => $sale->branch_id,
                    'notes' => 'Penjualan nota '.$sale->sale_number,
                ];
            }

            $movement = $this->stockMovementService->createDraft(
                movementData: [
                    'movement_number' => $this->makeMovementNumber(),
                    'movement_date' => $sale->sale_date,
                    'movement_type' => MovementType::BranchSale->value,
                    'status' => ApprovalStatus::Draft,
                    'from_branch_id' => $sale->branch_id,
                    'from_warehouse_id' => $branchWarehouseId,
                    'notes' => 'Auto movement dari nota '.$sale->sale_number,
                    'created_by' => $actor->id,
                    'reference_type' => $sale::class,
                    'reference_id' => $sale->id,
                ],
                items: $movementItems,
            );

            $this->stockMovementService->submit($movement);
            $this->stockMovementService->approve($movement, $actor);

            $sale->update([
                'status' => BranchSaleStatus::Posted,
                'posted_by' => $actor->id,
                'posted_at' => now(),
                'cogs_amount' => $cogsAmount,
                'gross_profit' => (float) $sale->total_amount - $cogsAmount,
            ]);

            $this->createIncomeFromSale($sale, $actor);

            $this->activityLogService->log(
                module: 'branch_sale',
                action: 'post_sale',
                subject: $sale,
                actor: $actor,
                after: [
                    'status' => $sale->status->value,
                    'total_amount' => (float) $sale->total_amount,
                    'cogs_amount' => (float) $sale->cogs_amount,
                ],
            );

            return $sale->fresh(['items.item', 'branch']);
        });
    }

    public function cancelDraft(BranchSale $sale, User $actor): BranchSale
    {
        if ($sale->status !== BranchSaleStatus::Draft) {
            throw new InvalidArgumentException('Hanya nota draft yang dapat dibatalkan.');
        }

        $sale->update(['status' => BranchSaleStatus::Cancelled]);

        $this->activityLogService->log(
            module: 'branch_sale',
            action: 'cancel_sale',
            subject: $sale,
            actor: $actor,
            after: ['status' => $sale->status->value],
        );

        return $sale;
    }

    protected function createIncomeFromSale(BranchSale $sale, User $actor): void
    {
        $category = FinanceCategory::query()->firstOrCreate(
            ['code' => 'REV-SALES'],
            [
                'name' => 'Sales Revenue',
                'type' => 'income',
                'is_cogs' => false,
                'is_active' => true,
            ],
        );

        FinanceIncome::query()->updateOrCreate(
            ['transaction_number' => $this->makeIncomeNumber($sale)],
            [
                'transaction_date' => $sale->sale_date,
                'branch_id' => $sale->branch_id,
                'finance_category_id' => $category->id,
                'amount' => (float) $sale->total_amount,
                'payment_method' => $sale->payment_method->value,
                'reference_type' => $sale::class,
                'reference_id' => $sale->id,
                'notes' => 'Auto income dari nota '.$sale->sale_number,
                'created_by' => $actor->id,
            ],
        );
    }

    protected function resolveSaleStageId(Item $item, ?int $branchStockStageId): int
    {
        if ($item->defaultStage?->code === ItemStageCode::Mro->value) {
            return (int) $item->default_stage_id;
        }

        if ($branchStockStageId) {
            return $branchStockStageId;
        }

        if ($item->default_stage_id) {
            return (int) $item->default_stage_id;
        }

        throw new InvalidArgumentException('Item '.$item->name.' belum memiliki stage default.');
    }

    protected function makeMovementNumber(): string
    {
        return 'SM-SALE-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
    }

    protected function makeIncomeNumber(BranchSale $sale): string
    {
        return 'INC-SALE-'.$sale->sale_number;
    }
}
