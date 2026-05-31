<?php

namespace App\Filament\Resources\StockBalances\Tables;

use App\Support\IndoNumber;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item.name')->label('Barang')->searchable()->sortable(),
                TextColumn::make('stage.name')->label('Tahap')->badge(),
                TextColumn::make('warehouse.name')->label('Gudang')->toggleable(),
                TextColumn::make('branch.name')->label('Cabang')->toggleable(),
                TextColumn::make('qty_on_hand')->label('Qty Stok')->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('avg_cost')->label('Harga Rata-rata')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('total_value')->label('Nilai Stok')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
            ])
            ->filters([
                SelectFilter::make('stage_id')->label('Tahap')->relationship('stage', 'name'),
            ]);
    }
}
