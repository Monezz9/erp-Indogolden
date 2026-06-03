<?php

namespace App\Filament\Resources\BranchSales\Tables;

use App\Enums\BranchSaleStatus;
use App\Models\BranchSale;
use App\Models\User;
use App\Services\BranchSaleService;
use App\Support\IndoNumber;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class BranchSalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale_number')->label('No. Nota')->searchable()->sortable(),
                TextColumn::make('sale_date')->label('Tanggal Nota')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('branch.name')->label('Cabang')->sortable(),
                TextColumn::make('cashier.name')->label('Kasir')->searchable()->sortable()->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (BranchSaleStatus|string|null $state): string => match (true) {
                        $state instanceof BranchSaleStatus => BranchSaleStatus::options()[$state->value] ?? $state->value,
                        $state === null || $state === '' => '-',
                        default => BranchSaleStatus::options()[$state] ?? $state,
                    }),
                TextColumn::make('payment_method')->label('Metode Bayar')->badge(),
                TextColumn::make('total_amount')->label('Total Nota')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('gross_profit')->label('Laba Kotor')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('creator.name')->label('Dibuat Oleh')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(BranchSaleStatus::options()),
                SelectFilter::make('branch_id')->relationship('branch', 'name')->label('Cabang'),
            ])
            ->recordActions([
                Action::make('post')
                    ->label('Posting Nota')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BranchSale $record): bool => $record->status === BranchSaleStatus::Draft && Gate::allows('post', $record))
                    ->action(function (BranchSale $record): void {
                        $actor = Auth::user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        try {
                            app(BranchSaleService::class)->post($record, $actor);

                            Notification::make()
                                ->title('Nota berhasil diposting')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal posting nota')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('print_thermal')
                    ->label('Cetak Thermal')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (BranchSale $record): string => route('branch-sales.print.thermal', ['branchSale' => $record]))
                    ->openUrlInNewTab(),
                Action::make('print_receipt')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (BranchSale $record): string => route('branch-sales.print.receipt', ['branchSale' => $record]))
                    ->openUrlInNewTab(),
                Action::make('print_a4')
                    ->label('Cetak A4')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->url(fn (BranchSale $record): string => route('branch-sales.print.a4', ['branchSale' => $record]))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
