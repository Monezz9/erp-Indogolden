<?php

namespace App\Filament\Resources\BranchSales\Pages;

use App\Enums\BranchSaleStatus;
use App\Enums\UserRole;
use App\Filament\Resources\BranchSales\BranchSaleResource;
use App\Models\User;
use App\Services\BranchSaleService;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateBranchSale extends CreateRecord
{
    protected static string $resource = BranchSaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if ($user instanceof User && $user->isBranchLike()) {
            $data['branch_id'] = $user->branch_id;
        }

        if (blank($data['sale_number'] ?? null)) {
            $data['sale_number'] = 'NOTA-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        }

        $data['status'] = BranchSaleStatus::Draft;
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        app(BranchSaleService::class)->syncTotals($this->record);
    }
}
