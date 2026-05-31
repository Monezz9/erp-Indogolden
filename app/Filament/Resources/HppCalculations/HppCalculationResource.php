<?php

namespace App\Filament\Resources\HppCalculations;

use App\Filament\Resources\HppCalculations\Pages\CreateHppCalculation;
use App\Filament\Resources\HppCalculations\Pages\EditHppCalculation;
use App\Filament\Resources\HppCalculations\Pages\ListHppCalculations;
use App\Filament\Resources\HppCalculations\Schemas\HppCalculationForm;
use App\Filament\Resources\HppCalculations\Tables\HppCalculationsTable;
use App\Models\HppCalculation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class HppCalculationResource extends Resource
{
    protected static ?string $model = HppCalculation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static \UnitEnum|string|null $navigationGroup = 'Analisis Operasional';

    protected static ?string $navigationLabel = 'Snapshot HPP';

    protected static ?string $modelLabel = 'Snapshot HPP';

    protected static ?string $pluralModelLabel = 'Snapshot HPP';

    public static function form(Schema $schema): Schema
    {
        return HppCalculationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HppCalculationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHppCalculations::route('/'),
            'create' => CreateHppCalculation::route('/create'),
            'edit' => EditHppCalculation::route('/{record}/edit'),
        ];
    }
}
