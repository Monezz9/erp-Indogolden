<?php

namespace App\Filament\Resources\FinanceExpenses\Tables;

use App\Support\IndoNumber;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinanceExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_number')->label('No. Transaksi')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Tanggal')->dateTime()->sortable(),
                TextColumn::make('branch.name')->label('Cabang')->toggleable(),
                TextColumn::make('category.name')->label('Kategori')->badge(),
                TextColumn::make('amount')->label('Nominal')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('payment_method')->label('Metode Bayar')->badge(),
            ])
            ->filters([
                SelectFilter::make('branch_id')->relationship('branch', 'name')->label('Cabang'),
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
