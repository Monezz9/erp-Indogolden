<?php

namespace App\Filament\Resources\FinanceExpenses\Tables;

use App\Support\FinanceBook;
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
            ->searchPlaceholder('Cari deskripsi, kategori, nomor transaksi...')
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->weight('semibold')
                    ->width('130px'),
                TextColumn::make('book_category')
                    ->label('Kategori')
                    ->state(fn ($record): string => FinanceBook::categoryLabel($record->category?->code, $record->category?->name, $record->category?->type))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Logistik' => 'warning',
                        'OPEX' => 'danger',
                        'NOPEX' => 'info',
                        default => 'gray',
                    })
                    ->width('150px'),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->state(fn ($record): string => FinanceBook::description($record))
                    ->searchable(['transaction_number', 'notes'])
                    ->wrap()
                    ->limit(80),
                TextColumn::make('debit')
                    ->label('Masuk (Debit)')
                    ->state('-')
                    ->alignCenter()
                    ->color('gray')
                    ->width('150px'),
                TextColumn::make('credit')
                    ->label('Keluar (Kredit)')
                    ->state(fn ($record): string => FinanceBook::rupiah($record->amount))
                    ->alignEnd()
                    ->color('danger')
                    ->weight('bold')
                    ->width('160px'),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->state(fn ($record): string => FinanceBook::rupiah(FinanceBook::runningExpenseBalance($record)))
                    ->alignEnd()
                    ->weight('bold')
                    ->width('160px'),
                TextColumn::make('branch.name')->label('Cabang')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_method')
                    ->label('Kas / Bank / Aplikasi')
                    ->state(fn ($record): string => FinanceBook::paymentLabel($record->payment_method))
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('transaction_number')->label('No. Transaksi')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('branch_id')->relationship('branch', 'name')->label('Cabang'),
                SelectFilter::make('finance_category_id')->relationship('category', 'name')->label('Kategori'),
                SelectFilter::make('payment_method')
                    ->label('Kas / Bank / Aplikasi')
                    ->options([
                        'cash' => 'Tunai',
                        'debit' => 'Debit',
                        'bank_transfer' => 'Transfer Bank',
                        'qris' => 'QRIS',
                        'other' => 'Lainnya',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-receipt-refund')
            ->emptyStateHeading('Belum ada pengeluaran')
            ->emptyStateDescription('Saat ada biaya keluar, tabel ini akan tampil sebagai buku kas kredit dengan saldo berjalan.');
    }
}
