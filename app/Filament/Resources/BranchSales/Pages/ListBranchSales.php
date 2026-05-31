<?php

namespace App\Filament\Resources\BranchSales\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\BranchSales\BranchSaleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBranchSales extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = BranchSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
