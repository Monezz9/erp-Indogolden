<?php

namespace App\Filament\Resources\ProductionRecipes\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\ProductionRecipes\ProductionRecipeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductionRecipes extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = ProductionRecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
