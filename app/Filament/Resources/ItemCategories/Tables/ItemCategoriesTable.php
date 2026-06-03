<?php

namespace App\Filament\Resources\ItemCategories\Tables;

use App\Support\InventoryLabels;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Kategori')->searchable()->sortable(),
                TextColumn::make('category_type')
                    ->label('Kegunaan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => InventoryLabels::categoryType($state)),
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
