<?php

namespace App\Filament\Resources\HppCalculations\Tables;

use App\Support\IndoNumber;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HppCalculationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('calc_number')->label('No. HPP')->searchable()->sortable(),
                TextColumn::make('calc_date')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('product_name')->label('Produk')->searchable()->toggleable(),
                TextColumn::make('stage')->label('Tahap')->badge(),
                TextColumn::make('hpp_per_unit')->label('HPP/Unit')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('selling_price')->label('Harga Jual')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('margin_percent')->label('Margin')->formatStateUsing(fn (mixed $state): string => IndoNumber::percent($state)),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
