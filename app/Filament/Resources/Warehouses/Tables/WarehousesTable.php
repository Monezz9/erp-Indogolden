<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama Gudang')->searchable(),
                TextColumn::make('branch.name')->label('Cabang')->toggleable(),
                TextColumn::make('location_type')
                    ->label('Tipe Lokasi')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => [
                        'central' => 'Gudang Pusat',
                        'branch' => 'Gudang Cabang',
                        'production' => 'Area Produksi',
                    ][$state] ?? (string) $state)
                    ->sortable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('location_type')
                    ->label('Tipe Lokasi')
                    ->options([
                        'central' => 'Gudang Pusat',
                        'branch' => 'Gudang Cabang',
                        'production' => 'Area Produksi',
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
