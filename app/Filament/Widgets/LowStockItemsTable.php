<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\User;
use App\Support\IndoNumber;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LowStockItemsTable extends TableWidget
{
    protected static ?string $heading = 'Stok Menipis';

    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasAnyRole([
            UserRole::Owner->value,
            UserRole::HeadLogistics->value,
            UserRole::LogisticsAdmin->value,
            UserRole::Finance->value,
            UserRole::Branch->value,
        ]);
    }

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $branchId = ($user instanceof User && $user->isBranchLike()) ? $user->branch_id : null;

        $stockSubQuery = StockBalance::query()
            ->selectRaw('item_id, SUM(qty_on_hand) as qty_on_hand_total')
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->groupBy('item_id');

        return $table
            ->query(fn (): Builder => Item::query()
                ->leftJoinSub($stockSubQuery, 'item_stocks', fn ($join) => $join->on('items.id', '=', 'item_stocks.item_id'))
                ->select('items.*')
                ->selectRaw('COALESCE(item_stocks.qty_on_hand_total, 0) as qty_on_hand_total')
                ->where('minimum_stock', '>', 0)
                ->orderBy('qty_on_hand_total'))
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('qty_on_hand_total')
                    ->label('Stok Tersedia')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('minimum_stock')
                    ->label('Minimum')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
            ]);
    }
}
