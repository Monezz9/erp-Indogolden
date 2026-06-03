<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GoodsReceiptService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected PurchaseOrderService $purchaseOrderService,
        protected ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>|null  $items
     */
    public function createDraftFromPurchaseOrder(PurchaseOrder $purchaseOrder, User $actor, ?array $items = null): GoodsReceipt
    {
        if (! in_array($purchaseOrder->status, [
            PurchaseOrderStatus::FinanceApproved,
            PurchaseOrderStatus::Ordered,
            PurchaseOrderStatus::PartiallyReceived,
        ], true)) {
            throw new InvalidArgumentException('PO belum siap untuk penerimaan barang.');
        }

        return DB::transaction(function () use ($purchaseOrder, $actor, $items): GoodsReceipt {
            $purchaseOrder->loadMissing(['items.item', 'items.unit']);

            $receipt = GoodsReceipt::query()->create([
                'receipt_number' => $this->makeNumber(),
                'purchase_order_id' => $purchaseOrder->id,
                'warehouse_id' => $purchaseOrder->warehouse_id,
                'receipt_date' => now()->toDateString(),
                'status' => GoodsReceiptStatus::Draft,
                'created_by' => $actor->id,
            ]);

            $lines = $items ?? $purchaseOrder->items
                ->filter(fn ($item): bool => $item->remainingQty() > 0)
                ->map(fn ($item): array => [
                    'purchase_order_item_id' => $item->id,
                    'item_id' => $item->item_id,
                    'unit_id' => $item->unit_id,
                    'purchase_unit_id' => $item->purchase_unit_id,
                    'purchase_qty' => $item->purchase_qty,
                    'conversion_qty' => $item->conversion_qty,
                    'ordered_qty' => $item->ordered_qty,
                    'received_qty' => $item->remainingQty(),
                    'unit_cost' => $item->unit_cost,
                    'purchase_unit_cost' => $item->purchase_unit_cost,
                ])
                ->all();

            foreach ($lines as $line) {
                $this->createItem($receipt, $line);
            }

            $this->activityLogService->log(
                module: 'goods_receipt',
                action: 'create_goods_receipt',
                subject: $receipt,
                after: $receipt->fresh(['items'])->toArray(),
                actor: $actor,
            );

            return $receipt->fresh(['items']);
        });
    }

    public function confirm(GoodsReceipt $receipt, User $actor): GoodsReceipt
    {
        if ($receipt->status !== GoodsReceiptStatus::Draft) {
            throw new InvalidArgumentException('Goods receipt yang bisa confirm harus draft.');
        }

        if ($receipt->items()->count() === 0) {
            throw new InvalidArgumentException('Tambahkan minimal 1 item sebelum confirm receive.');
        }

        return DB::transaction(function () use ($receipt, $actor): GoodsReceipt {
            $receipt->loadMissing(['purchaseOrder.items', 'items.item']);

            $movement = $this->stockMovementService->createDraft(
                movementData: [
                    'movement_number' => 'SM-'.now()->format('YmdHisv'),
                    'movement_date' => now(),
                    'movement_type' => MovementType::InboundPurchase->value,
                    'status' => ApprovalStatus::Draft,
                    'to_warehouse_id' => $receipt->warehouse_id,
                    'notes' => 'Goods receipt '.$receipt->receipt_number,
                    'created_by' => $actor->id,
                    'reference_type' => $receipt::class,
                    'reference_id' => $receipt->id,
                ],
                items: $receipt->items->map(fn ($item): array => [
                    'item_id' => $item->item_id,
                    'unit_id' => $item->unit_id,
                    'direction' => 'in',
                    'qty' => $item->received_qty,
                    'unit_cost' => $item->unit_cost,
                    'to_stage_id' => $item->item?->default_stage_id,
                    'to_warehouse_id' => $receipt->warehouse_id,
                ])->all(),
            );

            $this->stockMovementService->submit($movement);
            $this->stockMovementService->approve($movement, $actor);

            foreach ($receipt->items as $receiptItem) {
                if (! $receiptItem->purchase_order_item_id) {
                    continue;
                }

                $poItem = $receiptItem->purchaseOrderItem;
                $poItem?->update([
                    'received_qty' => (float) $poItem->received_qty + (float) $receiptItem->received_qty,
                ]);
            }

            $receipt->update([
                'status' => GoodsReceiptStatus::Confirmed,
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
            ]);

            $this->purchaseOrderService->syncReceiptStatus($receipt->purchaseOrder);

            $this->activityLogService->log(
                module: 'goods_receipt',
                action: 'confirm_goods_receipt',
                subject: $receipt,
                actor: $actor,
                after: ['status' => GoodsReceiptStatus::Confirmed->value],
            );

            return $receipt->fresh(['items', 'purchaseOrder']);
        });
    }

    public function cancel(GoodsReceipt $receipt, User $actor): GoodsReceipt
    {
        if ($receipt->status !== GoodsReceiptStatus::Draft) {
            throw new InvalidArgumentException('Hanya draft goods receipt yang bisa dibatalkan.');
        }

        $receipt->update(['status' => GoodsReceiptStatus::Cancelled]);

        $this->activityLogService->log(
            module: 'goods_receipt',
            action: 'cancel_goods_receipt',
            subject: $receipt,
            actor: $actor,
            after: ['status' => GoodsReceiptStatus::Cancelled->value],
        );

        return $receipt->refresh();
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    protected function createItem(GoodsReceipt $receipt, array $itemData): void
    {
        $receivedQty = (float) Arr::get($itemData, 'received_qty', 0);

        if ($receivedQty <= 0) {
            return;
        }

        $receipt->items()->create([
            'purchase_order_item_id' => Arr::get($itemData, 'purchase_order_item_id'),
            'item_id' => Arr::get($itemData, 'item_id'),
            'unit_id' => Arr::get($itemData, 'unit_id'),
            'purchase_unit_id' => Arr::get($itemData, 'purchase_unit_id', Arr::get($itemData, 'unit_id')),
            'purchase_qty' => Arr::get($itemData, 'purchase_qty', $receivedQty),
            'conversion_qty' => Arr::get($itemData, 'conversion_qty', 1),
            'ordered_qty' => Arr::get($itemData, 'ordered_qty', $receivedQty),
            'received_qty' => $receivedQty,
            'unit_cost' => Arr::get($itemData, 'unit_cost', 0),
            'purchase_unit_cost' => Arr::get($itemData, 'purchase_unit_cost', Arr::get($itemData, 'unit_cost', 0)),
            'notes' => Arr::get($itemData, 'notes'),
        ]);
    }

    protected function makeNumber(): string
    {
        $prefix = 'GR-'.now()->format('Ymd');
        $last = GoodsReceipt::query()
            ->where('receipt_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('receipt_number');

        $next = is_string($last) ? ((int) str($last)->afterLast('-')->toString()) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }
}
