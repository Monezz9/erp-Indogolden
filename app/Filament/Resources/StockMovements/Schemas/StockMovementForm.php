<?php

namespace App\Filament\Resources\StockMovements\Schemas;

use App\Enums\ApprovalStatus;
use App\Enums\MovementType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('movement_number')->label('No. Movement')->required()->maxLength(40)->unique(ignoreRecord: true),
                DateTimePicker::make('movement_date')->label('Tanggal Movement')->required()->default(now()),
                Select::make('movement_type')->label('Jenis Movement')->required()->options(MovementType::options()),
                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options(ApprovalStatus::options())
                    ->default(ApprovalStatus::Draft->value)
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
                        Select::make('item_id')->label('Barang')->relationship('item', 'name')->required()->searchable(),
                        Select::make('unit_id')->label('Satuan')->relationship('unit', 'name')->required()->searchable(),
                        Select::make('direction')->label('Arah Stok')->required()->options([
                            'in' => 'Masuk',
                            'out' => 'Keluar',
                            'loss' => 'Audit Susut',
                        ]),
                        TextInput::make('qty')->label('Qty')->required()->numeric(),
                        TextInput::make('unit_cost')->label('Harga Satuan')->numeric()->default(0),
                        Select::make('from_stage_id')->label('Dari Tahap')->relationship('fromStage', 'name')->searchable(),
                        Select::make('to_stage_id')->label('Ke Tahap')->relationship('toStage', 'name')->searchable(),
                        Select::make('from_warehouse_id')->label('Dari Gudang')->relationship('fromWarehouse', 'name')->searchable(),
                        Select::make('to_warehouse_id')->label('Ke Gudang')->relationship('toWarehouse', 'name')->searchable(),
                        Select::make('from_branch_id')->label('Dari Cabang')->relationship('fromBranch', 'name')->searchable(),
                        Select::make('to_branch_id')->label('Ke Cabang')->relationship('toBranch', 'name')->searchable(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
