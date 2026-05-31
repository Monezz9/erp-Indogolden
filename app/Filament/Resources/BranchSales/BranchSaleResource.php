<?php

namespace App\Filament\Resources\BranchSales;

use App\Enums\UserRole;
use App\Filament\Resources\BranchSales\Pages\CreateBranchSale;
use App\Filament\Resources\BranchSales\Pages\EditBranchSale;
use App\Filament\Resources\BranchSales\Pages\ListBranchSales;
use App\Filament\Resources\BranchSales\Schemas\BranchSaleForm;
use App\Filament\Resources\BranchSales\Tables\BranchSalesTable;
use App\Models\BranchSale;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BranchSaleResource extends Resource
{
    protected static ?string $model = BranchSale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Cabang';

    protected static ?string $navigationLabel = 'Penjualan Cabang';

    protected static ?string $modelLabel = 'Penjualan Cabang';

    protected static ?string $pluralModelLabel = 'Penjualan Cabang';

    public static function form(Schema $schema): Schema
    {
        return BranchSaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchSalesTable::configure($table);
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
            'index' => ListBranchSales::route('/'),
            'create' => CreateBranchSale::route('/create'),
            'edit' => EditBranchSale::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Penjualan Cabang';
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
            UserRole::LogisticsAdmin->value,
        ])) {
            return $query;
        }

        if ($user->isBranchLike() && $user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
