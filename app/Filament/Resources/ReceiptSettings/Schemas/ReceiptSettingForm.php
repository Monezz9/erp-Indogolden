<?php

namespace App\Filament\Resources\ReceiptSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReceiptSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('store_name')
                    ->label('Nama Toko')
                    ->maxLength(255),
                TextInput::make('store_phone')
                    ->label('Nomor WA/Telepon')
                    ->maxLength(50),
                Textarea::make('store_address')
                    ->label('Alamat Toko')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('footer_text')
                    ->label('Footer Text')
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('paper_size')
                    ->label('Ukuran Kertas')
                    ->options([
                        '58mm' => '58mm',
                        '80mm' => '80mm',
                        'A4' => 'A4',
                    ])
                    ->default('80mm')
                    ->required(),
                Toggle::make('show_logo')
                    ->label('Tampilkan Logo')
                    ->default(false),
                FileUpload::make('logo_path')
                    ->label('Upload Logo')
                    ->image()
                    ->disk('public')
                    ->directory('receipt/logo')
                    ->visibility('public'),
                Toggle::make('show_qris')
                    ->label('Tampilkan QRIS')
                    ->default(false),
                FileUpload::make('qris_image_path')
                    ->label('Upload QRIS')
                    ->image()
                    ->disk('public')
                    ->directory('receipt/qris')
                    ->visibility('public'),
                Toggle::make('show_discount')
                    ->label('Tampilkan Diskon')
                    ->default(true),
                Toggle::make('show_tax')
                    ->label('Tampilkan Pajak')
                    ->default(true),
            ]);
    }
}
