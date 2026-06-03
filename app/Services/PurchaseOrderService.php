<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createDraft(array $data, array $items, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $items, $actor): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::query()->create([
                'po_number' => $data['po_number'] ?? $this->makeNumber(),
                'supplier_id' => Arr::get($data, 'supplier_id'),
                'warehouse_id' => Arr::get($data, 'warehouse_id'),
                'order_date' => Arr::get($data, 'order_date', now()->toDateString()),
                'expected_date' => Arr::get($data, 'expected_date'),
                'status' => PurchaseOrderStatus::Draft,
                'shipping_cost' => Arr::get($data, 'shipping_cost', 0),
                'notes' => Arr::get($data, 'notes'),
                'created_by' => $actor->id,
            ]);

            foreach ($items as $itemData) {
                $this->createItem($purchaseOrder, $itemData);
            }

            $this->recalculateTotals($purchaseOrder);

            $this->activityLogService->log(
                module: 'procurement',
                action: 'create_purchase_order',
                subject: $purchaseOrder,
                after: $purchaseOrder->fresh(['items'])->toArray(),
                actor: $actor,
            );

            return $purchaseOrder->fresh(['items']);
        });
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    public function addItem(PurchaseOrder $purchaseOrder, array $itemData): PurchaseOrder
    {
        $this->assertCanTransition($purchaseOrder, [PurchaseOrderStatus::Draft], 'tambah item');

        $this->createItem($purchaseOrder, $itemData);
        $this->recalculateTotals($purchaseOrder);

        return $purchaseOrder->fresh(['items']);
    }

    public function submit(PurchaseOrder $purchaseOrder, User $actor): PurchaseOrder
    {
        $this->assertCanTransition($purchaseOrder, [PurchaseOrderStatus::Draft], 'submit');

        if ($purchaseOrder->items()->count() === 0) {
            throw new InvalidArgumentException('Tambahkan minimal 1 item sebelum submit PO.');
        }

        return $this->updateStatus(
            $purchaseOrder,
            PurchaseOrderStatus::Submitted,
            $actor,
            ['submitted_by' => $actor->id, 'submitted_at' => now()],
            'submit_purchase_order',
        );
    }

    public function financeApprove(PurchaseOrder $purchaseOrder, User $actor, ?string $notes = null): PurchaseOrder
    {
        $this->assertCanTransition($purchaseOrder, [PurchaseOrderStatus::Submitted], 'approve finance');

        return $this->updateStatus(
            $purchaseOrder,
            PurchaseOrderStatus::FinanceApproved,
            $actor,
            [
                'finance_reviewed_by' => $actor->id,
                'finance_reviewed_at' => now(),
                'finance_notes' => $notes ?? $purchaseOrder->finance_notes,
            ],
            'finance_approve_purchase_order',
        );
    }

    public function financeReject(PurchaseOrder $purchaseOrder, User $actor, ?string $notes = null): PurchaseOrder
    {
        $this->assertCanTransition($purchaseOrder, [PurchaseOrderStatus::Submitted], 'reject finance');

        return $this->updateStatus(
            $purchaseOrder,
            PurchaseOrderStatus::FinanceRejected,
            $actor,
            [
                'finance_reviewed_by' => $actor->id,
                'finance_reviewed_at' => now(),
                'finance_notes' => $notes ?? $purchaseOrder->finance_notes,
            ],
            'finance_reject_purchase_order',
        );
    }

    public function markOrdered(PurchaseOrder $purchaseOrder, User $actor): PurchaseOrder
    {
        $this->assertCanTransition($purchaseOrder, [PurchaseOrderStatus::FinanceApproved], 'mark ordered');

        return $this->updateStatus($purchaseOrder, PurchaseOrderStatus::Ordered, $actor, [], 'mark_purchase_order_ordered');
    }

    public function cancel(PurchaseOrder $purchaseOrder, User $actor): PurchaseOrder
    {
        $this->assertCanTransition($purchaseOrder, [
            PurchaseOrderStatus::Draft,
            PurchaseOrderStatus::Submitted,
            PurchaseOrderStatus::FinanceRejected,
        ], 'cancel');

        return $this->updateStatus($purchaseOrder, PurchaseOrderStatus::Cancelled, $actor, [], 'cancel_purchase_order');
    }

    public function syncReceiptStatus(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $purchaseOrder->load('items');

        $orderedQty = (float) $purchaseOrder->items->sum('ordered_qty');
        $receivedQty = (float) $purchaseOrder->items->sum('received_qty');

        if ($orderedQty > 0 && $receivedQty >= $orderedQty) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Received]);
        } elseif ($receivedQty > 0) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::PartiallyReceived]);
        } elseif ($purchaseOrder->status === PurchaseOrderStatus::FinanceApproved) {
            $purchaseOrder->update(['status' => PurchaseOrderStatus::Ordered]);
        }

        return $purchaseOrder->refresh();
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    protected function createItem(PurchaseOrder $purchaseOrder, array $itemData): void
    {
        $purchaseQty = (float) Arr::get($itemData, 'purchase_qty', Arr::get($itemData, 'ordered_qty', 0));
        $conversionQty = (float) Arr::get($itemData, 'conversion_qty', 1);
        $qty = (float) Arr::get($itemData, 'ordered_qty', $purchaseQty * $conversionQty);
        $purchaseUnitCost = (float) Arr::get($itemData, 'purchase_unit_cost', Arr::get($itemData, 'unit_cost', 0));
        $taxAmount = (float) Arr::get($itemData, 'tax_amount', 0);
        $lineTotal = (float) Arr::get($itemData, 'line_total', ($purchaseQty * $purchaseUnitCost) + $taxAmount);
        $unitCost = $qty > 0 ? (($lineTotal - $taxAmount) / $qty) : 0;

        if ($qty <= 0) {
            throw new InvalidArgumentException('Qty PO harus lebih besar dari 0.');
        }

        $purchaseOrder->items()->create([
            'item_id' => Arr::get($itemData, 'item_id'),
            'unit_id' => Arr::get($itemData, 'unit_id'),
            'purchase_unit_id' => Arr::get($itemData, 'purchase_unit_id', Arr::get($itemData, 'unit_id')),
            'purchase_qty' => $purchaseQty,
            'conversion_qty' => $conversionQty,
            'ordered_qty' => $qty,
            'unit_cost' => $unitCost,
            'purchase_unit_cost' => $purchaseUnitCost,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
            'notes' => Arr::get($itemData, 'notes'),
        ]);
    }

    protected function recalculateTotals(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->load('items');

        $subtotal = (float) $purchaseOrder->items->sum(fn ($item): float => (float) $item->line_total - (float) $item->tax_amount);
        $taxTotal = (float) $purchaseOrder->items->sum('tax_amount');
        $shipping = (float) $purchaseOrder->shipping_cost;

        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $subtotal + $taxTotal + $shipping,
        ]);
    }

    /**
     * @param  array<int, PurchaseOrderStatus>  $allowed
     */
    protected function assertCanTransition(PurchaseOrder $purchaseOrder, array $allowed, string $action): void
    {
        if (in_array($purchaseOrder->status, $allowed, true)) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Aksi %s tidak valid untuk status %s.',
            $action,
            $purchaseOrder->status->value,
        ));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function updateStatus(PurchaseOrder $purchaseOrder, PurchaseOrderStatus $status, User $actor, array $extra, string $action): PurchaseOrder
    {
        $before = $purchaseOrder->toArray();

        $purchaseOrder->update(array_merge(['status' => $status], $extra));

        $this->activityLogService->log(
            module: 'procurement',
            action: $action,
            subject: $purchaseOrder,
            before: $before,
            after: $purchaseOrder->fresh()->toArray(),
            actor: $actor,
        );

        return $purchaseOrder->refresh();
    }

    protected function makeNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ymd');
        $last = PurchaseOrder::query()
            ->where('po_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('po_number');

        $next = is_string($last) ? ((int) str($last)->afterLast('-')->toString()) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }
}
