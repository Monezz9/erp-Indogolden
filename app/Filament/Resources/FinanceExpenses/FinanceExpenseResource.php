<?php

namespace App\Filament\Resources\FinanceExpenses;

use App\Enums\UserRole;
use App\Filament\Resources\FinanceExpenses\Pages\CreateFinanceExpense;
use App\Filament\Resources\FinanceExpenses\Pages\EditFinanceExpense;
use App\Filament\Resources\FinanceExpenses\Pages\ListFinanceExpenses;
use App\Filament\Resources\FinanceExpenses\Schemas\FinanceExpenseForm;
use App\Filament\Resources\FinanceExpenses\Tables\FinanceExpensesTable;
use App\Models\FinanceExpense;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinanceExpenseResource extends Resource
{
    protected static ?string $model = FinanceExpense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static \UnitEnum|string|null $navigationGroup = 'Finance';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pengeluaran';

    protected static ?string $pluralModelLabel = 'Pengeluaran';

    public static function form(Schema $schema): Schema
    {
        return FinanceExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinanceExpensesTable::configure($table);
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
            'index' => ListFinanceExpenses::route('/'),
            'create' => CreateFinanceExpense::route('/create'),
            'edit' => EditFinanceExpense::route('/{record}/edit'),
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
