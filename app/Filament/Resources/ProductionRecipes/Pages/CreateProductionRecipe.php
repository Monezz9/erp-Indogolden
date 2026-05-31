<?php

namespace App\Filament\Resources\ProductionRecipes\Pages;

use App\Filament\Resources\ProductionRecipes\ProductionRecipeResource;
use App\Filament\Resources\Pages\CreateRecord;

class CreateProductionRecipe extends CreateRecord
{
    protected static string $resource = ProductionRecipeResource::class;
}
