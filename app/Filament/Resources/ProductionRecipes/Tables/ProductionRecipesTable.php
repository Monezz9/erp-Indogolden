<?php

namespace App\Filament\Resources\ProductionRecipes\Tables;

use App\Support\IndoNumber;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductionRecipesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama Resep')->searchable(),
                TextColumn::make('outputItem.name')->label('Barang Jadi'),
                TextColumn::make('output_qty')->label('Qty Output')->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('yield_percentage')->label('Yield')->formatStateUsing(fn (mixed $state): string => IndoNumber::percent($state)),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                //
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
