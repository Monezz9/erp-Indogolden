<?php

namespace App\Filament\Resources\ProductionOrders\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductionOrders extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = ProductionOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
