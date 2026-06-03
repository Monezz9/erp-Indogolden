<?php

namespace App\Filament\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\PurchaseOrderService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProcurementFinanceReviewWorkspace extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected string $view = 'filament.pages.procurement-finance-review-workspace';

    protected static ?string $navigationLabel = 'Review Finance Pengadaan';

    protected static \UnitEnum|string|null $navigationGroup = 'Pengadaan';

    protected static ?int $navigationSort = 2;

    public string $status = 'all';

    public ?string $financeNotes = null;

    public ?int $reviewPurchaseOrderId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isAdminLike() || $user->hasRole(UserRole::Finance->value));
    }

    public function approve(int $purchaseOrderId, PurchaseOrderService $service): void
    {
        $this->review($purchaseOrderId, $service, true);
    }

    public function reject(int $purchaseOrderId, PurchaseOrderService $service): void
    {
        $this->review($purchaseOrderId, $service, false);
    }

    /**
     * @return Collection<int, PurchaseOrder>
     */
    public function rows(): Collection
    {
        return PurchaseOrder::query()
            ->with(['supplier', 'warehouse', 'items.item', 'items.unit', 'items.purchaseUnit'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest('id')
            ->limit(100)
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

    protected function review(int $purchaseOrderId, PurchaseOrderService $service, bool $approved): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            $purchaseOrder = PurchaseOrder::query()->findOrFail($purchaseOrderId);

            if ($approved) {
                $service->financeApprove($purchaseOrder, $user, $this->financeNotes);
                Notification::make()->title('PO disetujui Finance')->success()->send();
            } else {
                $service->financeReject($purchaseOrder, $user, $this->financeNotes);
                Notification::make()->title('PO ditolak Finance')->warning()->send();
            }

            $this->financeNotes = null;
        } catch (Throwable $exception) {
            Notification::make()->title('Gagal review PO')->body($exception->getMessage())->danger()->send();
        }
    }
}
