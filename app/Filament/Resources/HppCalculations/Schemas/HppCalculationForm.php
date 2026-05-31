<?php

namespace App\Filament\Resources\HppCalculations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HppCalculationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('calc_number')->label('No. HPP')->required()->maxLength(50)->unique(ignoreRecord: true),
                DatePicker::make('calc_date')->label('Tanggal HPP')->required()->default(now()),
                Select::make('branch_id')->label('Cabang')->relationship('branch', 'name')->searchable(),
                TextInput::make('product_name')->label('Nama Produk')->maxLength(150),
                Select::make('stage')->label('Tahap')->options([
                    'raw_material' => 'Bahan Mentah',
                    'grooming' => 'Grooming',
                    'sorted_raw_material' => 'Bahan Mentah Sortir',
                    'production' => 'Produksi',
                    'finish_goods' => 'Barang Jadi',
                ])->required(),
                TextInput::make('total_raw_value')->label('Nilai Bahan Mentah')->numeric()->minValue(0)->prefix('Rp'),
                TextInput::make('total_clean_value')->label('Nilai Bahan Bersih')->numeric()->minValue(0)->prefix('Rp'),
                TextInput::make('total_production_cost')->label('Total Biaya Produksi')->numeric()->minValue(0)->prefix('Rp'),
                TextInput::make('hpp_per_unit')->label('HPP / Unit')->numeric()->minValue(0)->prefix('Rp'),
                TextInput::make('selling_price')->label('Harga Jual')->numeric()->minValue(0)->prefix('Rp'),
                TextInput::make('profit')->label('Profit')->numeric()->prefix('Rp'),
                TextInput::make('margin_percent')->label('Margin')->numeric()->suffix('%'),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            ]);
    }
}
