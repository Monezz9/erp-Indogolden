<?php

namespace App\Filament\Resources\FinanceIncomes;

use App\Enums\UserRole;
use App\Filament\Resources\FinanceIncomes\Pages\CreateFinanceIncome;
use App\Filament\Resources\FinanceIncomes\Pages\EditFinanceIncome;
use App\Filament\Resources\FinanceIncomes\Pages\ListFinanceIncomes;
use App\Filament\Resources\FinanceIncomes\Schemas\FinanceIncomeForm;
use App\Filament\Resources\FinanceIncomes\Tables\FinanceIncomesTable;
use App\Models\FinanceIncome;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinanceIncomeResource extends Resource
{
    protected static ?string $model = FinanceIncome::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Pemasukan';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pemasukan';

    protected static ?string $pluralModelLabel = 'Pemasukan';

    public static function form(Schema $schema): Schema
    {
        return FinanceIncomeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinanceIncomesTable::configure($table);
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
            'index' => ListFinanceIncomes::route('/'),
            'create' => CreateFinanceIncome::route('/create'),
            'edit' => EditFinanceIncome::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query;
        }

        if ($user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::Finance->value,
            UserRole::HeadLogistics->value,
        ])) {
            return $query;
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
