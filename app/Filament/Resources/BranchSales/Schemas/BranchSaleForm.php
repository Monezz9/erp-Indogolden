<?php

namespace App\Filament\Resources\BranchSales\Schemas;

use App\Enums\BranchSaleStatus;
use App\Enums\PaymentMethod;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BranchSaleForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $isBranchUser = $user instanceof User && $user->isBranchLike();

        return $schema
            ->components([
                TextInput::make('sale_number')
                    ->label('No. Nota')
                    ->required()
                    ->maxLength(40)
                    ->default(fn () => 'NOTA-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)))
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('sale_date')
                    ->label('Tanggal Nota')
                    ->required()
                    ->default(now()),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->default($isBranchUser ? $user?->branch_id : null)
                    ->disabled($isBranchUser)
                    ->dehydrated(),
                Select::make('status')
                    ->label('Status')
                    ->options(BranchSaleStatus::options())
                    ->default(BranchSaleStatus::Draft->value)
                    ->required()
                    ->disabled()
                    ->dehydrated(),
                Select::make('payment_method')
                    ->label('Metode Bayar')
                    ->options(PaymentMethod::options())
                    ->default(PaymentMethod::Cash->value)
                    ->required(),
                TextInput::make('subtotal')->label('Subtotal')->numeric()->default(0)->disabled()->dehydrated(),
                TextInput::make('discount_amount')->label('Diskon')->numeric()->default(0),
                TextInput::make('tax_amount')->label('Pajak')->numeric()->default(0),
                TextInput::make('total_amount')->label('Total Nota')->numeric()->default(0)->disabled()->dehydrated(),
                TextInput::make('cogs_amount')->label('HPP')->numeric()->default(0)->disabled()->dehydrated(),
                TextInput::make('gross_profit')->label('Laba Kotor')->numeric()->default(0)->disabled()->dehydrated(),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Select::make('item_id')
                            ->label('Barang')
                            ->relationship('item', 'name')
                            ->searchable()
                            ->required(),
                        Select::make('unit_id')
                            ->label('Satuan')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->required(),
                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->required(),
                        TextInput::make('unit_price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->required(),
                        TextInput::make('line_total')
                            ->label('Total Baris')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('cogs_unit')
                            ->label('HPP Satuan')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('cogs_total')
                            ->label('Total HPP')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('notes')->label('Catatan'),
                    ])
                    ->columnSpanFull()
                    ->defaultItems(1),
            ]);
    }
}
