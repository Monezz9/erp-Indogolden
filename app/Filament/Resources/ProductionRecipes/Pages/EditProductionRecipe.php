<?php

namespace App\Filament\Resources\ProductionRecipes\Pages;

use App\Filament\Resources\ProductionRecipes\ProductionRecipeResource;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecord;

class EditProductionRecipe extends EditRecord
{
    protected static string $resource = ProductionRecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }
}
