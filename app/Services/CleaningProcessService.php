<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ItemStageCode;
use App\Enums\MovementType;
use App\Models\CleaningProcess;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemStage;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CleaningProcessService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected ActivityLogService $activityLogService,
    ) {}

    public function post(array $data, User $actor): CleaningProcess
    {
        $inputQty = (float) ($data['input_qty'] ?? 0);
        $outputQty = (float) ($data['output_qty'] ?? 0);

        if ($inputQty <= 0) {
            throw new InvalidArgumentException('Qty masuk pembersihan harus lebih besar dari 0.');
        }

        if ($outputQty <= 0) {
            throw new InvalidArgumentException('Qty hasil bersih harus lebih besar dari 0.');
        }

        if ($outputQty > $inputQty) {
            throw new InvalidArgumentException('Qty hasil bersih tidak boleh lebih besar dari qty masuk.');
        }

        return DB::transaction(function () use ($data, $actor, $inputQty, $outputQty): CleaningProcess {
            $rawDirtyStageId = $this->stageId(ItemStageCode::RawDirty);
            $rawCleanStageId = $this->stageId(ItemStageCode::RawClean);
            $item = Item::query()->findOrFail((int) $data['item_id']);
            $outputItem = isset($data['output_item_id'])
                ? Item::query()->findOrFail((int) $data['output_item_id'])
                : $this->resolveOutputItem($item, $rawCleanStageId);
            $warehouseId = (int) $data['warehouse_id'];

            $balance = StockBalance::query()
                ->where('item_id', $item->id)
                ->where('stage_id', $rawDirtyStageId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if (! $balance || (float) $balance->qty_on_hand < $inputQty) {
                throw new InvalidArgumentException(sprintf(
                    'Stok mentah kotor tidak cukup. Tersedia %s, diminta %s.',
                    $balance ? (float) $balance->qty_on_hand : 0,
                    $inputQty,
                ));
            }

            $inputUnitCost = (float) $balance->avg_cost;
            $totalInputCost = $inputQty * $inputUnitCost;
            $outputUnitCost = $totalInputCost / $outputQty;
            $shrinkageQty = $inputQty - $outputQty;
            $shrinkagePercent = ($shrinkageQty / $inputQty) * 100;

            $process = CleaningProcess::query()->create([
                'process_number' => $data['process_number'] ?? $this->makeNumber(),
                'process_date' => $data['process_date'] ?? now()->toDateString(),
                'warehouse_id' => $warehouseId,
                'item_id' => $item->id,
                'output_item_id' => $outputItem->id,
                'unit_id' => (int) ($data['unit_id'] ?? $outputItem->default_unit_id),
                'input_qty' => $inputQty,
                'output_qty' => $outputQty,
                'shrinkage_qty' => $shrinkageQty,
                'shrinkage_percent' => $shrinkagePercent,
                'input_unit_cost' => $inputUnitCost,
                'output_unit_cost' => $outputUnitCost,
                'total_input_cost' => $totalInputCost,
                'status' => 'posted',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'posted_at' => now(),
            ]);

            $outMovement = $this->stockMovementService->createDraft([
                'movement_number' => 'SM-'.now()->format('YmdHisv'),
                'movement_date' => now(),
                'movement_type' => MovementType::CleaningConversion->value,
                'status' => ApprovalStatus::Draft,
                'from_warehouse_id' => $warehouseId,
                'notes' => 'Pembersihan bahan '.$process->process_number,
                'created_by' => $actor->id,
                'reference_type' => $process::class,
                'reference_id' => $process->id,
            ], [[
                'item_id' => $item->id,
                'unit_id' => $process->unit_id,
                'direction' => 'out',
                'qty' => $inputQty,
                'unit_cost' => $inputUnitCost,
                'from_stage_id' => $rawDirtyStageId,
                'from_warehouse_id' => $warehouseId,
            ]]);

            $this->stockMovementService->submit($outMovement);
            $this->stockMovementService->approve($outMovement, $actor);

            $inMovement = $this->stockMovementService->createDraft([
                'movement_number' => 'SM-'.now()->addSecond()->format('YmdHisv'),
                'movement_date' => now(),
                'movement_type' => MovementType::CleaningConversion->value,
                'status' => ApprovalStatus::Draft,
                'to_warehouse_id' => $warehouseId,
                'notes' => 'Hasil pembersihan bahan '.$process->process_number,
                'created_by' => $actor->id,
                'reference_type' => $process::class,
                'reference_id' => $process->id,
            ], [[
                'item_id' => $outputItem->id,
                'unit_id' => $process->unit_id,
                'direction' => 'in',
                'qty' => $outputQty,
                'unit_cost' => $outputUnitCost,
                'to_stage_id' => $rawCleanStageId,
                'to_warehouse_id' => $warehouseId,
            ]]);

            $this->stockMovementService->submit($inMovement);
            $this->stockMovementService->approve($inMovement, $actor);

            $this->activityLogService->log(
                module: 'production',
                action: 'post_cleaning_process',
                subject: $process,
                actor: $actor,
                after: $process->toArray(),
            );

            return $process->fresh(['item', 'outputItem', 'warehouse', 'unit']);
        });
    }

    protected function stageId(ItemStageCode $code): int
    {
        return (int) ItemStage::query()->where('code', $code->value)->value('id');
    }

    protected function makeNumber(): string
    {
        $prefix = 'CLN-'.now()->format('Ymd');
        $last = CleaningProcess::query()
            ->where('process_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('process_number');

        $next = is_string($last) ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    protected function resolveOutputItem(Item $inputItem, int $rawCleanStageId): Item
    {
        $categoryId = ItemCategory::query()->where('slug', 'raw-clean')->value('id');

        if (! $categoryId) {
            throw new InvalidArgumentException('Kategori Raw Clean belum tersedia.');
        }

        $existing = Item::query()
            ->where('default_stage_id', $rawCleanStageId)
            ->where('item_category_id', $categoryId)
            ->where('name', $inputItem->name)
            ->first();

        if ($existing) {
            return $existing;
        }

        $baseSku = preg_replace('/^(RM|RC)-/', '', $inputItem->sku) ?: $inputItem->sku;
        $sku = $this->uniqueRawCleanSku('RC-'.$baseSku);

        return Item::query()->create([
            'sku' => $sku,
            'name' => $inputItem->name,
            'item_category_id' => $categoryId,
            'default_unit_id' => $inputItem->default_unit_id,
            'default_stage_id' => $rawCleanStageId,
            'item_type' => 'material',
            'requires_production' => false,
            'is_perishable' => $inputItem->is_perishable,
            'minimum_stock' => 0,
            'purchase_price' => $inputItem->purchase_price,
            'selling_price' => $inputItem->selling_price,
            'latest_weighted_avg_cost' => 0,
            'description' => 'Hasil pembersihan dari '.$inputItem->sku,
            'is_active' => true,
        ]);
    }

    protected function uniqueRawCleanSku(string $sku): string
    {
        $candidate = $sku;
        $suffix = 2;

        while (Item::query()->where('sku', $candidate)->exists()) {
            $candidate = $sku.'-'.$suffix++;
        }

        return $candidate;
    }
}
