<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\GoodsReceiptStatus;
use App\Enums\MovementType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Models\FinanceCategory;
use App\Models\FinanceExpense;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GoodsReceiptService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected PurchaseOrderService $purchaseOrderService,
        protected ActivityLogService $activityLogService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createDirectProcurement(array $data, array $items, User $actor): GoodsReceipt
    {
        $this->validateDirectProcurement($data, $items);

        return DB::transaction(function () use ($data, $items, $actor): GoodsReceipt {
            $receipt = GoodsReceipt::query()->create([
                'receipt_number' => Arr::get($data, 'receipt_number', $this->makeNumberWithPrefix('PG')),
                'purchase_order_id' => null,
                'supplier_id' => Arr::get($data, 'supplier_id'),
                'warehouse_id' => Arr::get($data, 'warehouse_id'),
                'receipt_date' => Arr::get($data, 'receipt_date', now()->toDateString()),
                'invoice_number' => Arr::get($data, 'invoice_number'),
                'status' => GoodsReceiptStatus::Draft,
                'notes' => trim((string) Arr::get($data, 'notes')),
                'created_by' => $actor->id,
            ]);

            $subtotal = 0.0;

            foreach ($items as $line) {
                $item = Item::query()->findOrFail((int) Arr::get($line, 'item_id'));
                $purchaseQty = (float) Arr::get($line, 'purchase_qty', Arr::get($line, 'received_qty', 0));
                $conversionQty = (float) Arr::get($line, 'conversion_qty', 1);
                $receivedQty = (float) Arr::get($line, 'received_qty', $purchaseQty * $conversionQty);
                $lineTotal = (float) Arr::get($line, 'line_total', 0);
                $unitCost = $receivedQty > 0 ? $lineTotal / $receivedQty : 0;
                $purchaseUnitCost = $purchaseQty > 0 ? $lineTotal / $purchaseQty : 0;

                $subtotal += $lineTotal;

                $this->createItem($receipt, [
                    'purchase_order_item_id' => null,
                    'item_id' => $item->id,
                    'unit_id' => Arr::get($line, 'unit_id', $item->default_unit_id),
                    'purchase_unit_id' => Arr::get($line, 'purchase_unit_id', Arr::get($line, 'unit_id', $item->default_unit_id)),
                    'purchase_qty' => $purchaseQty,
                    'conversion_qty' => $conversionQty,
                    'ordered_qty' => $receivedQty,
                    'received_qty' => $receivedQty,
                    'unit_cost' => $unitCost,
                    'purchase_unit_cost' => $purchaseUnitCost,
                    'notes' => trim((string) Arr::get($line, 'notes', Arr::get($data, 'notes'))),
                ]);
            }

            $receipt->update([
                'subtotal' => $subtotal,
                'grand_total' => $subtotal,
            ]);

            $receipt = $this->confirm($receipt->fresh(['items.item']), $actor);
            $this->recordFinanceExpense($receipt, $actor);

            return $receipt->fresh(['items.item', 'supplier', 'stockMovements.items']);
        });
    }

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
                    'notes' => trim((string) ($receipt->notes ?: 'Pengadaan '.$receipt->receipt_number)),
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
                    'notes' => $item->notes ?: $receipt->notes,
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

            if ($receipt->purchaseOrder) {
                $this->purchaseOrderService->syncReceiptStatus($receipt->purchaseOrder);
            }

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

    protected function makeNumberWithPrefix(string $prefixCode): string
    {
        $prefix = $prefixCode.'-'.now()->format('Ymd');
        $last = GoodsReceipt::query()
            ->where('receipt_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('receipt_number');

        $next = is_string($last) ? ((int) str($last)->afterLast('-')->toString()) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    protected function makeNumber(): string
    {
        return $this->makeNumberWithPrefix('GR');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function validateDirectProcurement(array $data, array $items): void
    {
        $errors = [];

        if (blank(Arr::get($data, 'supplier_id'))) {
            $errors['supplier_id'] = 'Supplier wajib diisi.';
        }

        if (blank(Arr::get($data, 'receipt_date'))) {
            $errors['receipt_date'] = 'Tanggal pengadaan wajib diisi.';
        }

        if ($items === []) {
            $errors['items'] = 'Minimal 1 item wajib diisi.';
        }

        foreach ($items as $index => $line) {
            $key = 'items.'.$index;

            if (blank(Arr::get($line, 'item_id'))) {
                $errors[$key.'.item_id'] = 'Barang wajib diisi.';
            }

            if ((float) Arr::get($line, 'purchase_qty', 0) <= 0) {
                $errors[$key.'.purchase_qty'] = 'Qty datang harus lebih besar dari 0.';
            }

            if ((float) Arr::get($line, 'line_total', 0) <= 0) {
                $errors[$key.'.line_total'] = 'Harga beli harus lebih besar dari 0.';
            }

            if (blank(Arr::get($line, 'purchase_unit_id'))) {
                $errors[$key.'.purchase_unit_id'] = 'Satuan beli wajib diisi.';
            }

            if (blank(Arr::get($line, 'unit_id'))) {
                $errors[$key.'.unit_id'] = 'Satuan stok wajib diisi.';
            }

            if ((int) Arr::get($line, 'purchase_unit_id') !== (int) Arr::get($line, 'unit_id')
                && (float) Arr::get($line, 'conversion_qty', 0) <= 0) {
                $errors[$key.'.conversion_qty'] = 'Konversi satuan wajib jika satuan beli berbeda dengan satuan stok.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function recordFinanceExpense(GoodsReceipt $receipt, User $actor): void
    {
        $category = FinanceCategory::query()->firstOrCreate(
            ['code' => 'EXP-PROCUREMENT'],
            [
                'name' => 'Pengadaan',
                'type' => 'expense',
                'is_cogs' => true,
                'is_active' => true,
            ],
        );

        FinanceExpense::query()->updateOrCreate(
            [
                'reference_type' => $receipt::class,
                'reference_id' => $receipt->id,
            ],
            [
                'transaction_number' => 'PROC-'.$receipt->receipt_number,
                'transaction_date' => $receipt->receipt_date,
                'branch_id' => $receipt->warehouse?->branch_id,
                'supplier_id' => $receipt->supplier_id,
                'finance_category_id' => $category->id,
                'amount' => $receipt->grand_total,
                'payment_method' => PaymentMethod::Cash,
                'notes' => trim('Pengadaan '.$receipt->receipt_number.' '.$receipt->notes),
                'created_by' => $actor->id,
            ],
        );
    }
}
