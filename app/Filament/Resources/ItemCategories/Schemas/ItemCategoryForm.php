<?php

namespace App\Filament\Resources\ItemCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ItemCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nama Kategori Barang')->required()->maxLength(255),
                Select::make('category_type')
                    ->label('Kegunaan')
                    ->helperText('Menentukan alur stok: bahan dibeli, proses produksi, barang jadi, operasional, atau analisis.')
                    ->required()
                    ->options([
                        'raw_material' => 'Bahan / Material',
                        'wip' => 'Proses Produksi',
                        'finished_goods' => 'Barang Jadi',
                        'mro' => 'Operasional',
                        'analysis' => 'Analisis',
                        'other' => 'Lainnya',
                    ]),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }
}
