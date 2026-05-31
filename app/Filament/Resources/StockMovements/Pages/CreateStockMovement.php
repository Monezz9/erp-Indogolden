<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = ApprovalStatus::Draft;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
