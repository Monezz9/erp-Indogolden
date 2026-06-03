<?php

namespace App\Filament\Pages;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\GoodsReceiptService;
use App\Services\PurchaseOrderService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProcurementRequestWorkspace extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected string $view = 'filament.pages.procurement-request-workspace';

    protected static ?string $title = 'Ruang Kerja Permintaan Pengadaan';

    protected static ?string $navigationLabel = 'Permintaan Pengadaan';

    protected static \UnitEnum|string|null $navigationGroup = 'Pengadaan';

    protected static ?int $navigationSort = 1;

    public ?int $supplierId = null;

    public ?int $warehouseId = null;

    public ?string $expectedDate = null;

    public ?string $transactionNumber = null;

    public ?string $orderDate = null;

    public ?string $notes = null;

    public ?int $itemId = null;

    public ?string $itemSearch = null;

    public bool $showItemSearchResults = false;

    public ?int $unitId = null;

    public ?int $purchaseUnitId = null;

    public ?string $itemKind = null;

    public float $purchaseQty = 1;

    public float $conversionQty = 1;

    public float $orderedQty = 1;

    public float $unitCost = 0;

    public float $taxAmount = 0;

    public string $status = 'all';

    /**
     * @var array<int, array{item_id: int, item_label: string, item_name: string, item_kind: ?string, unit_id: int, unit_label: string, purchase_unit_id: int, purchase_unit_label: string, purchase_qty: float, conversion_qty: float, ordered_qty: float, line_total: float, purchase_unit_cost: float, unit_cost: float, notes: ?string}>
     */
    public array $cart = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isAdminLike() || $user->isWarehouseLike());
    }

    public function mount(): void
    {
        $this->transactionNumber = $this->nextTransactionNumber();
        $this->orderDate = now()->toDateString();
        $this->expectedDate = $this->orderDate;
        $this->warehouseId = $this->centralWarehouseId();
    }

    public function addItemToCart(): void
    {
        if (! $this->itemId) {
            Notification::make()->title('Pilih item terlebih dahulu')->warning()->send();

            return;
        }

        try {
            $item = Item::query()->with('category')->findOrFail($this->itemId);
            $unitId = $this->unitId ?: $item->default_unit_id;
            $purchaseUnitId = $this->purchaseUnitId ?: $unitId;

            if (! $unitId || ! $purchaseUnitId) {
                Notification::make()->title('Satuan item belum lengkap')->warning()->send();

                return;
            }

            if ($this->purchaseQty <= 0 || $this->conversionQty <= 0 || $this->lineTotal() <= 0) {
                Notification::make()->title('Qty, isi/satuan, dan total harga harus lebih besar dari 0')->warning()->send();

                return;
            }

            $unitOptions = $this->unitOptions();

            $this->cart[] = [
                'item_id' => $item->id,
                'item_label' => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
                'item_name' => $item->name,
                'item_kind' => $this->itemKind ?: $item->category?->name,
                'unit_id' => $unitId,
                'unit_label' => $unitOptions[$unitId] ?? '-',
                'purchase_unit_id' => $purchaseUnitId,
                'purchase_unit_label' => $unitOptions[$purchaseUnitId] ?? '-',
                'purchase_qty' => $this->purchaseQty,
                'conversion_qty' => $this->conversionQty,
                'ordered_qty' => $this->baseQty(),
                'line_total' => $this->lineTotal(),
                'purchase_unit_cost' => $this->purchaseUnitCost(),
                'unit_cost' => $this->baseUnitCost(),
                'notes' => ($this->itemKind ?: $item->category?->name) ? 'Kategori: '.($this->itemKind ?: $item->category?->name) : null,
            ];

            $this->resetLineInput();

            Notification::make()->title('Barang ditambahkan ke draft PO')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal menambahkan barang')->body($exception->getMessage())->danger()->send();
        }
    }

    public function removeCartItem(int $index): void
    {
        unset($this->cart[$index]);

        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function createPurchaseOrder(PurchaseOrderService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        if (! $this->supplierId) {
            Notification::make()->title('Pilih supplier terlebih dahulu')->warning()->send();

            return;
        }

        if ($this->cart === []) {
            Notification::make()->title('Tambahkan minimal 1 barang ke draft PO')->warning()->send();

            return;
        }

        try {
            $this->warehouseId = $this->centralWarehouseId();

            $service->createDraft([
                'po_number' => $this->transactionNumber,
                'supplier_id' => $this->supplierId,
                'warehouse_id' => $this->warehouseId,
                'order_date' => $this->orderDate,
                'expected_date' => $this->orderDate,
                'notes' => $this->notes,
            ], array_map(fn (array $line): array => [
                'item_id' => $line['item_id'],
                'unit_id' => $line['unit_id'],
                'purchase_unit_id' => $line['purchase_unit_id'],
                'purchase_qty' => $line['purchase_qty'],
                'conversion_qty' => $line['conversion_qty'],
                'ordered_qty' => $line['ordered_qty'],
                'unit_cost' => $line['unit_cost'],
                'purchase_unit_cost' => $line['purchase_unit_cost'],
                'line_total' => $line['line_total'],
                'tax_amount' => 0,
                'notes' => $line['notes'],
            ], $this->cart), $user);

            $purchaseOrder = PurchaseOrder::query()
                ->where('po_number', $this->transactionNumber)
                ->firstOrFail();

            $service->submit($purchaseOrder, $user);
            $service->financeApprove($purchaseOrder->refresh(), $user);

            $this->reset(['notes', 'cart']);
            $this->resetLineInput();
            $this->taxAmount = 0;
            $this->transactionNumber = $this->nextTransactionNumber();
            $this->orderDate = now()->toDateString();
            $this->expectedDate = $this->orderDate;

            Notification::make()->title('Pengadaan disimpan')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal membuat PO')->body($exception->getMessage())->danger()->send();
        }
    }

    public function updatedItemId(?int $itemId): void
    {
        $item = Item::query()
            ->with(['category:id,name', 'defaultUnit:id,code'])
            ->find($itemId);

        $this->fillSelectedItem($item);
    }

    public function updatedItemSearch(?string $value): void
    {
        $this->itemId = null;
        $this->itemKind = null;
        $this->unitId = null;
        $this->purchaseUnitId = null;
        $this->conversionQty = 1;
        $this->unitCost = 0;
        $this->showItemSearchResults = trim((string) $value) !== '';
    }

    public function openItemSearchResults(): void
    {
        $this->showItemSearchResults = trim((string) $this->itemSearch) !== '';
    }

    public function selectItem(int $itemId): void
    {
        $item = Item::query()
            ->with(['category:id,name', 'defaultUnit:id,code'])
            ->where('is_active', true)
            ->find($itemId);

        $this->fillSelectedItem($item);
        $this->showItemSearchResults = false;
    }

    public function updatedPurchaseUnitId(): void
    {
        $this->conversionQty = $this->defaultConversionQty($this->purchaseUnitId, $this->unitId);
    }

    public function updatedUnitId(): void
    {
        $this->conversionQty = $this->defaultConversionQty($this->purchaseUnitId, $this->unitId);
    }

    public function submit(int $purchaseOrderId, PurchaseOrderService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $service->submit(PurchaseOrder::query()->findOrFail($purchaseOrderId), $user);
            Notification::make()->title('PO diajukan ke Finance')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal mengajukan PO')->body($exception->getMessage())->danger()->send();
        }
    }

    public function cancel(int $purchaseOrderId, PurchaseOrderService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $service->cancel(PurchaseOrder::query()->findOrFail($purchaseOrderId), $user);
            Notification::make()->title('PO dibatalkan')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal membatalkan PO')->body($exception->getMessage())->danger()->send();
        }
    }

    public function createReceipt(int $purchaseOrderId, GoodsReceiptService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $purchaseOrder = PurchaseOrder::query()
                ->with('items')
                ->findOrFail($purchaseOrderId);

            $service->createDraftFromPurchaseOrder($purchaseOrder, $user);

            Notification::make()->title('Draft penerimaan dibuat')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal membuat penerimaan')->body($exception->getMessage())->danger()->send();
        }
    }

    public function confirmReceipt(int $receiptId, GoodsReceiptService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $service->confirm(GoodsReceipt::query()->findOrFail($receiptId), $user);

            Notification::make()->title('Penerimaan barang dikonfirmasi')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal konfirmasi penerimaan')->body($exception->getMessage())->danger()->send();
        }
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function rows(): Collection
    {
        return PurchaseOrder::query()
            ->with([
                'supplier',
                'warehouse',
                'items.item',
                'items.purchaseUnit',
                'items.unit',
                'goodsReceipts.items.item',
                'goodsReceipts.items.purchaseUnit',
                'goodsReceipts.items.unit',
            ])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest('id')
            ->limit(100)
            ->get();
    }

    public function canCreateReceipt(PurchaseOrder $purchaseOrder): bool
    {
        if (! in_array($purchaseOrder->status, [
            PurchaseOrderStatus::FinanceApproved,
            PurchaseOrderStatus::Ordered,
            PurchaseOrderStatus::PartiallyReceived,
        ], true)) {
            return false;
        }

        if ($purchaseOrder->goodsReceipts->contains(fn (GoodsReceipt $receipt): bool => $receipt->status === GoodsReceiptStatus::Draft)) {
            return false;
        }

        return $purchaseOrder->items->sum(fn ($item): float => $item->remainingQty()) > 0;
    }

    public function supplierOptions(): array
    {
        return Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    public function warehouseOptions(): array
    {
        return Warehouse::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    public function centralWarehouseName(): string
    {
        return Warehouse::query()->whereKey($this->centralWarehouseId())->value('name') ?? 'Gudang Pusat';
    }

    protected function centralWarehouseId(): ?int
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('code', 'WH-CENTRAL')
                    ->orWhere('location_type', 'central')
                    ->orWhere('name', 'Gudang Pusat');
            })
            ->orderByRaw("CASE WHEN code = 'WH-CENTRAL' THEN 0 ELSE 1 END")
            ->value('id');
    }

    public function itemSearchResults(): array
    {
        $search = trim((string) $this->itemSearch);

        return Item::query()
            ->with('category:id,name')
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('sku', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('sku')
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'sku', 'name', 'item_category_id'])
            ->map(fn (Item $item): array => [
                'id' => $item->id,
                'label' => $this->itemSearchLabel($item),
                'category' => $item->category?->name ?? '-',
            ])
            ->all();
    }

    public function unitOptions(): array
    {
        return Unit::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Unit $unit): array => [
                $unit->id => trim($unit->code.' - '.$unit->name),
            ])
            ->all();
    }

    public function selectedItem(): ?Item
    {
        if (! $this->itemId) {
            return null;
        }

        return Item::query()
            ->with(['category:id,name', 'defaultUnit:id,code'])
            ->find($this->itemId);
    }

    public function selectedItemCategoryLabel(): string
    {
        return $this->selectedItem()?->category?->name ?? '-';
    }

    public function selectedStockUnitCode(): string
    {
        if (! $this->unitId) {
            return 'stok';
        }

        return Unit::query()->whereKey($this->unitId)->value('code') ?? 'stok';
    }

    public function lineTotal(): float
    {
        return $this->unitCost;
    }

    public function baseQty(): float
    {
        return $this->purchaseQty * $this->conversionQty;
    }

    public function baseUnitCost(): float
    {
        $baseQty = $this->baseQty();

        return $baseQty > 0 ? $this->lineTotal() / $baseQty : 0;
    }

    public function purchaseUnitCost(): float
    {
        return $this->purchaseQty > 0 ? $this->lineTotal() / $this->purchaseQty : 0;
    }

    public function cartTotal(): float
    {
        return array_sum(array_column($this->cart, 'line_total'));
    }

    public function statusOptions(): array
    {
        return ['all' => 'Semua'] + PurchaseOrderStatus::options();
    }

    protected function nextTransactionNumber(): string
    {
        $prefix = 'PO-'.now()->format('Ymd');
        $last = PurchaseOrder::query()
            ->where('po_number', 'like', $prefix.'-%')
            ->latest('id')
            ->value('po_number');

        $next = is_string($last) ? ((int) str($last)->afterLast('-')->toString()) + 1 : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    protected function resetLineInput(): void
    {
        $this->reset(['itemId', 'itemSearch', 'unitId', 'purchaseUnitId', 'itemKind']);
        $this->purchaseQty = 1;
        $this->conversionQty = 1;
        $this->orderedQty = 1;
        $this->unitCost = 0;
    }

    protected function fillSelectedItem(?Item $item, bool $syncSearch = true): void
    {
        $this->itemId = $item?->id;
        $this->unitId = $item?->default_unit_id;
        $this->purchaseUnitId = $this->defaultPurchaseUnitId($item);
        $this->conversionQty = $this->defaultConversionQty($this->purchaseUnitId, $this->unitId);
        $this->unitCost = (float) ($item?->purchase_price ?? 0);
        $this->itemKind = $item?->category?->name;

        if ($syncSearch) {
            $this->itemSearch = $item ? $this->itemSearchLabel($item) : null;
        }
    }

    protected function resolveItemSearch(?string $value): ?Item
    {
        $search = trim((string) $value);

        if ($search === '') {
            return null;
        }

        $exact = Item::query()
            ->with(['category:id,name', 'defaultUnit:id,code'])
            ->where('is_active', true)
            ->where(function ($query) use ($search): void {
                $query->where('sku', $search)
                    ->orWhere('name', $search);
            })
            ->first();

        if ($exact) {
            return $exact;
        }

        return Item::query()
            ->with(['category:id,name', 'defaultUnit:id,code'])
            ->where('is_active', true)
            ->where(function ($query) use ($search): void {
                $query->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            })
            ->limit(25)
            ->get()
            ->first(fn (Item $item): bool => $this->itemSearchLabel($item) === $search);
    }

    protected function itemSearchLabel(Item $item): string
    {
        return trim(($item->sku ? $item->sku.' - ' : '').$item->name);
    }

    protected function defaultPurchaseUnitId(?Item $item): ?int
    {
        if (! $item?->default_unit_id) {
            return null;
        }

        if (strtoupper((string) $item->defaultUnit?->code) === 'GR') {
            return Unit::query()->where('code', 'KG')->value('id') ?? $item->default_unit_id;
        }

        return $item->default_unit_id;
    }

    protected function defaultConversionQty(?int $purchaseUnitId, ?int $stockUnitId): float
    {
        if (! $purchaseUnitId || ! $stockUnitId) {
            return 1;
        }

        $codes = Unit::query()
            ->whereIn('id', [$purchaseUnitId, $stockUnitId])
            ->pluck('code', 'id');

        $purchaseCode = strtoupper((string) ($codes[$purchaseUnitId] ?? ''));
        $stockCode = strtoupper((string) ($codes[$stockUnitId] ?? ''));

        return match (true) {
            $purchaseCode === $stockCode => 1,
            $purchaseCode === 'KG' && in_array($stockCode, ['GR', 'G', 'GRAM'], true) => 1000,
            in_array($purchaseCode, ['GR', 'G', 'GRAM'], true) && $stockCode === 'KG' => 0.001,
            default => 1,
        };
    }
}
