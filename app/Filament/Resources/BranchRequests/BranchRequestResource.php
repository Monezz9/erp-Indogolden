<?php

namespace App\Filament\Resources\BranchRequests;

use App\Enums\UserRole;
use App\Filament\Resources\BranchRequests\Pages\CreateBranchRequest;
use App\Filament\Resources\BranchRequests\Pages\EditBranchRequest;
use App\Filament\Resources\BranchRequests\Pages\ListBranchRequests;
use App\Filament\Resources\BranchRequests\Schemas\BranchRequestForm;
use App\Filament\Resources\BranchRequests\Tables\BranchRequestsTable;
use App\Models\BranchRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BranchRequestResource extends Resource
{
    protected static ?string $model = BranchRequest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static \UnitEnum|string|null $navigationGroup = 'Operasional Cabang';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Request Barang Cabang';

    protected static ?string $modelLabel = 'Request Barang Cabang';

    protected static ?string $pluralModelLabel = 'Request Barang Cabang';

    public static function form(Schema $schema): Schema
    {
        return BranchRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranchRequests::route('/'),
            'create' => CreateBranchRequest::route('/create'),
            'edit' => EditBranchRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user instanceof User) {
            return $query;
        }

        if ($user->isAdminLike() || $user->isWarehouseLike()) {
            return $query;
        }

        if ($user->isBranchLike() && $user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Admin->value,
            UserRole::Gudang->value,
            UserRole::Cabang->value,
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
            UserRole::LogisticsAdmin->value,
            UserRole::Branch->value,
        ]);
    }
}
