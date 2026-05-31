<?php

namespace App\Filament\Resources\Items\Tables;

use App\Support\IndoNumber;
use App\Support\InventoryLabels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama Barang')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->badge()->searchable(),
                TextColumn::make('item_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => InventoryLabels::itemType($state)),
                TextColumn::make('defaultUnit.code')->label('Satuan')->badge()->sortable(),
                TextColumn::make('stock_qty')
                    ->label('Stok')
                    ->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state))
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('minimum_stock')->label('Stok Minimum')->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('purchase_price')->label('Harga Beli')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('selling_price')->label('Harga Jual')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('latest_weighted_avg_cost')->label('Harga Pokok / HPP')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state))->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('description')->label('Keterangan')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('item_category_id')->relationship('category', 'name')->label('Kategori'),
                SelectFilter::make('default_unit_id')->relationship('defaultUnit', 'code')->label('Satuan'),
                SelectFilter::make('item_type')
                    ->label('Tipe')
                    ->options([
                        'material' => 'Barang',
                        'semi_finished' => 'Setengah Jadi',
                        'product' => 'Produk Jadi',
                        'packaging' => 'Kemasan',
                        'service' => 'Jasa',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Tidak Aktif',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
