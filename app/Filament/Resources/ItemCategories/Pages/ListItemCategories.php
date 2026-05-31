<?php

namespace App\Filament\Resources\ItemCategories\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\ItemCategories\ItemCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItemCategories extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = ItemCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            ...$this->getExcelHeaderActions(),
        ];
    }
}
