<?php

namespace App\Filament\Resources\ItemStages;

use App\Filament\Resources\ItemStages\Pages\CreateItemStage;
use App\Filament\Resources\ItemStages\Pages\EditItemStage;
use App\Filament\Resources\ItemStages\Pages\ListItemStages;
use App\Filament\Resources\ItemStages\Schemas\ItemStageForm;
use App\Filament\Resources\ItemStages\Tables\ItemStagesTable;
use App\Models\ItemStage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ItemStageResource extends Resource
{
    protected static ?string $model = ItemStage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Tahap Barang';

    protected static \UnitEnum|string|null $navigationGroup = 'Data Master';

    protected static ?string $modelLabel = 'Tahap Barang';

    protected static ?string $pluralModelLabel = 'Tahap Barang';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return ItemStageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemStagesTable::configure($table);
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
            'index' => ListItemStages::route('/'),
            'create' => CreateItemStage::route('/create'),
            'edit' => EditItemStage::route('/{record}/edit'),
        ];
    }
}
