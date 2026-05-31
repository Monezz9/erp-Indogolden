<?php

namespace App\Filament\Resources\ProductionRecipes\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductionRecipeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Kode Resep')->required()->maxLength(30)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nama Resep')->required()->maxLength(255),
                Select::make('output_item_id')
                    ->label('Barang Jadi')
                    ->relationship('outputItem', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('output_unit_id')
                    ->label('Satuan Output')
                    ->relationship('outputUnit', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('output_qty')->label('Qty Output')->required()->numeric(),
                TextInput::make('yield_percentage')->label('Yield (%)')->numeric()->default(100),
                Toggle::make('is_active')->label('Aktif')->default(true),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                Repeater::make('ingredients')
                    ->relationship('ingredients')
                    ->schema([
                        Select::make('item_id')
                            ->label('Bahan')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->required(),
                        Select::make('unit_id')
                            ->label('Satuan')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->required(),
                        Select::make('stage_id')
                            ->label('Tahap')
                            ->relationship('stage', 'name')
                            ->searchable(),
                        TextInput::make('qty')->label('Qty')->numeric()->required(),
                        Toggle::make('is_optional')->label('Opsional')->default(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
