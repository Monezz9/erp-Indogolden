<?php

namespace App\Filament\Resources\ReceiptSettings\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReceiptSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('store_name')->label('Nama Toko')->searchable(),
                TextColumn::make('paper_size')->label('Ukuran Kertas')->badge(),
                IconColumn::make('show_logo')->boolean()->label('Logo'),
                IconColumn::make('show_qris')->boolean()->label('QRIS'),
                IconColumn::make('show_discount')->boolean()->label('Diskon'),
                IconColumn::make('show_tax')->boolean()->label('Pajak'),
                TextColumn::make('updated_at')->since()->label('Terakhir Diubah'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
