<?php

namespace App\Filament\Resources\Cashiers;

use App\Filament\Resources\Cashiers\Pages\CreateCashier;
use App\Filament\Resources\Cashiers\Pages\EditCashier;
use App\Filament\Resources\Cashiers\Pages\ListCashiers;
use App\Filament\Resources\Cashiers\Schemas\CashierForm;
use App\Filament\Resources\Cashiers\Tables\CashiersTable;
use App\Models\Cashier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashierResource extends Resource
{
    protected static ?string $model = Cashier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Kasir';

    protected static \UnitEnum|string|null $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $modelLabel = 'Kasir';

    protected static ?string $pluralModelLabel = 'Kasir';

    public static function form(Schema $schema): Schema
    {
        return CashierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashiersTable::configure($table);
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
            'index' => ListCashiers::route('/'),
            'create' => CreateCashier::route('/create'),
            'edit' => EditCashier::route('/{record}/edit'),
        ];
    }
}
