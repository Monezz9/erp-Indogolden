<?php

namespace App\Filament\Pages;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\GoodsReceiptService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class GoodsReceiveWorkspace extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected string $view = 'filament.pages.goods-receive-workspace';

    protected static ?string $navigationLabel = 'Penerimaan Barang';

    protected static \UnitEnum|string|null $navigationGroup = 'Pengadaan';

    protected static ?int $navigationSort = 3;

    public string $receiptStatus = 'draft';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isAdminLike() || $user->isWarehouseLike());
    }

    public function createReceipt(int $purchaseOrderId, GoodsReceiptService $service): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $purchaseOrder = PurchaseOrder::query()->with('items')->findOrFail($purchaseOrderId);
            $service->createDraftFromPurchaseOrder($purchaseOrder, $user);
            Notification::make()->title('Draft penerimaan dibuat')->success()->send();
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal membuat penerimaan')->body($exception->getMessage())->danger()->send();
        }
    }

    public function confirm(int $receiptId, GoodsReceiptService $service): void
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
    public function receivablePurchaseOrders(): Collection
    {
        return PurchaseOrder::query()
            ->receivable()
            ->with(['supplier', 'warehouse', 'items.item'])
            ->latest('id')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, GoodsReceipt>
     */
    public function receipts(): Collection
    {
        return GoodsReceipt::query()
            ->with(['purchaseOrder.supplier', 'warehouse', 'items.item', 'items.unit'])
            ->when($this->receiptStatus !== 'all', fn ($query) => $query->where('status', $this->receiptStatus))
            ->latest('id')
            ->limit(100)
            ->get();
    }

    public function receiptStatusOptions(): array
    {
        return ['all' => 'Semua'] + GoodsReceiptStatus::options();
    }
}
