<?php

namespace App\Filament\Pages;

use App\Enums\PurchaseOrderStatus;
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

class ProcurementHistory extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected string $view = 'filament.pages.procurement-history';

    protected static ?string $title = 'LIST PENGADAAN';

    protected static ?string $navigationLabel = 'LIST PENGADAAN';

    protected static \UnitEnum|string|null $navigationGroup = 'Pengadaan';

    protected static ?int $navigationSort = 4;

    public string $status = 'all';

    public ?int $reviewPurchaseOrderId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isAdminLike() || $user->isWarehouseLike());
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
            ->limit(150)
            ->get();
    }

    public function statusOptions(): array
    {
        return ['all' => 'Semua'] + PurchaseOrderStatus::options();
    }

    public function toggleReview(int $purchaseOrderId): void
    {
        $this->reviewPurchaseOrderId = $this->reviewPurchaseOrderId === $purchaseOrderId
            ? null
            : $purchaseOrderId;
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
}
