<?php

namespace App\Filament\Resources\ProductionRecipes;

use App\Filament\Resources\ProductionRecipes\Pages\CreateProductionRecipe;
use App\Filament\Resources\ProductionRecipes\Pages\EditProductionRecipe;
use App\Filament\Resources\ProductionRecipes\Pages\ListProductionRecipes;
use App\Filament\Resources\ProductionRecipes\Schemas\ProductionRecipeForm;
use App\Filament\Resources\ProductionRecipes\Tables\ProductionRecipesTable;
use App\Models\ProductionRecipe;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductionRecipeResource extends Resource
{
    protected static ?string $model = ProductionRecipe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Resep Produksi';

    protected static \UnitEnum|string|null $navigationGroup = 'Produksi';

    protected static ?string $modelLabel = 'Resep Produksi';

    protected static ?string $pluralModelLabel = 'Resep Produksi';

    public static function form(Schema $schema): Schema
    {
        return ProductionRecipeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductionRecipesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductionRecipes::route('/'),
            'create' => CreateProductionRecipe::route('/create'),
            'edit' => EditProductionRecipe::route('/{record}/edit'),
        ];
    }
}
