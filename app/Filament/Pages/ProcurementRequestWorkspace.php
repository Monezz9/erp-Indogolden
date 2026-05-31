<?php

namespace App\Filament\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
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

    public ?int $unitId = null;

    public ?string $itemKind = null;

    public float $orderedQty = 1;

    public float $unitCost = 0;

    public float $taxAmount = 0;

    public string $status = 'all';

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

    public function createPurchaseOrder(PurchaseOrderService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $this->warehouseId = $this->centralWarehouseId();

            $item = Item::query()->findOrFail($this->itemId);

            $service->createDraft([
                'po_number' => $this->transactionNumber,
                'supplier_id' => $this->supplierId,
                'warehouse_id' => $this->warehouseId,
                'order_date' => $this->orderDate,
                'expected_date' => $this->orderDate,
                'notes' => $this->notes,
            ], [[
                'item_id' => $item->id,
                'unit_id' => $this->unitId ?: $item->default_unit_id,
                'ordered_qty' => $this->orderedQty,
                'unit_cost' => $this->unitCost,
                'tax_amount' => 0,
                'notes' => $this->itemKind ? 'Kategori: '.$this->itemKind : null,
            ]], $user);

            $this->reset(['notes', 'itemId', 'unitId', 'itemKind']);
            $this->orderedQty = 1;
            $this->unitCost = 0;
            $this->taxAmount = 0;
            $this->transactionNumber = $this->nextTransactionNumber();
            $this->orderDate = now()->toDateString();
            $this->expectedDate = $this->orderDate;

            Notification::make()->title('PO draft dibuat')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal membuat PO')->body($exception->getMessage())->danger()->send();
        }
    }

    public function updatedItemId(?int $itemId): void
    {
        $item = Item::query()->find($itemId);

        $this->unitId = $item?->default_unit_id;
        $this->unitCost = (float) ($item?->purchase_price ?? 0);
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

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function rows(): Collection
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'warehouse', 'items.item', 'items.unit'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest('id')
            ->limit(100)
            ->get();
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

    public function itemOptions(): array
    {
        return Item::query()
            ->orderBy('name')
            ->get(['id', 'sku', 'name'])
            ->mapWithKeys(fn (Item $item): array => [
                $item->id => trim(($item->sku ? $item->sku.' - ' : '').$item->name),
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

    public function itemKindOptions(): array
    {
        return [
            'Mentah Keringan' => 'Mentah Keringan',
            'Mentah Bumbu' => 'Mentah Bumbu',
            'Premix' => 'Premix',
        ];
    }

    public function selectedItem(): ?Item
    {
        if (! $this->itemId) {
            return null;
        }

        return Item::query()
            ->with(['defaultUnit:id,code'])
            ->find($this->itemId);
    }

    public function lineTotal(): float
    {
        return $this->orderedQty * $this->unitCost;
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
}
