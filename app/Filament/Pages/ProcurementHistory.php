<?php

namespace App\Filament\Pages;

use App\Enums\GoodsReceiptStatus;
use App\Models\GoodsReceipt;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
     * @return Collection<int, GoodsReceipt>
     */
    public function rows(): Collection
    {
        return GoodsReceipt::query()
            ->with([
                'supplier',
                'warehouse',
                'items.item.category',
                'items.purchaseUnit',
                'items.unit',
            ])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest('id')
            ->limit(150)
            ->get();
    }

    public function statusOptions(): array
    {
        return ['all' => 'Semua'] + GoodsReceiptStatus::options();
    }

    public function toggleReview(int $purchaseOrderId): void
    {
        $this->reviewPurchaseOrderId = $this->reviewPurchaseOrderId === $purchaseOrderId
            ? null
            : $purchaseOrderId;
    }
}
