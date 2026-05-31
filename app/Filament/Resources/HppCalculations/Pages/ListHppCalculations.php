<?php

namespace App\Filament\Resources\HppCalculations\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\HppCalculations\HppCalculationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHppCalculations extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = HppCalculationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
