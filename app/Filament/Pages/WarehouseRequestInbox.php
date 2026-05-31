<?php

namespace App\Filament\Pages;

use App\Enums\BranchRequestStatus;
use App\Filament\Resources\BranchRequests\BranchRequestResource;
use App\Models\Branch;
use App\Models\BranchRequest;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WarehouseRequestInbox extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected string $view = 'filament.pages.warehouse-request-inbox';

    protected static ?string $navigationLabel = 'Request Masuk Gudang';

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Cabang';

    protected static ?int $navigationSort = 1;

    public ?string $deliveryDate = null;

    public string $status = 'submitted';

    public ?int $branchId = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ($user->isAdminLike() || $user->isWarehouseLike());
    }

    public function mount(): void
    {
        $this->deliveryDate = now()->addDay()->toDateString();
    }

    /**
     * @return Collection<int, BranchRequest>
     */
    public function rows(): Collection
    {
        return BranchRequest::query()
            ->with(['branch', 'items.product'])
            ->when($this->deliveryDate, fn ($q) => $q->whereDate('delivery_date', $this->deliveryDate))
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->latest('delivery_date')
            ->limit(100)
            ->get();
    }

    public function statusOptions(): array
    {
        return ['all' => 'Semua'] + BranchRequestStatus::options();
    }

    public function branchOptions(): array
    {
        return Branch::query()->pluck('name', 'id')->all();
    }

    public function editUrl(int $id): string
    {
        return BranchRequestResource::getUrl('edit', ['record' => $id]);
    }
}
