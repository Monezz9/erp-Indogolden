<?php

namespace App\Filament\Resources\Transfers\Schemas;

use App\Enums\TransferStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('transfer_number')->label('No. Transfer')->required()->maxLength(40)->unique(ignoreRecord: true),
                DateTimePicker::make('transfer_date')->label('Tanggal Transfer')->required()->default(now()),
                Select::make('status')
                    ->label('Status')
                    ->options(TransferStatus::options())
                    ->default(TransferStatus::Draft->value)
                    ->required()
                    ->disabled()
                    ->dehydrated(),
                Select::make('from_warehouse_id')->label('Dari Gudang')->relationship('fromWarehouse', 'name')->searchable()->preload(),
                Select::make('to_warehouse_id')->label('Ke Gudang')->relationship('toWarehouse', 'name')->searchable()->preload(),
                Select::make('from_branch_id')->label('Dari Cabang')->relationship('fromBranch', 'name')->searchable()->preload(),
                Select::make('to_branch_id')->label('Ke Cabang')->relationship('toBranch', 'name')->searchable()->preload(),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Select::make('item_id')->label('Barang')->relationship('item', 'name')->searchable()->required(),
                        Select::make('unit_id')->label('Satuan')->relationship('unit', 'name')->searchable()->required(),
                        TextInput::make('requested_qty')->label('Qty Diminta')->numeric()->required(),
                        TextInput::make('approved_qty')->label('Qty Disetujui')->numeric()->default(0),
                        TextInput::make('shipped_qty')->label('Qty Dikirim')->numeric()->default(0),
                        TextInput::make('received_qty')->label('Qty Diterima')->numeric()->default(0),
                        TextInput::make('unit_cost')->label('Harga Satuan')->numeric()->default(0),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
