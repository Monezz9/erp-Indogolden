<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\ApprovalStatus;
use App\Enums\MovementType;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockMovementService;
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

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movement_number')->label('No. Movement')->searchable()->sortable(),
                TextColumn::make('movement_date')->label('Tanggal')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('movement_type')
                    ->label('Jenis Movement')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match (true) {
                        $state === null || $state === '' => '-',
                        default => MovementType::options()[$state] ?? $state,
                    })
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ApprovalStatus|string|null $state): string => match (true) {
                        $state instanceof ApprovalStatus => ApprovalStatus::options()[$state->value] ?? $state->value,
                        $state === null || $state === '' => '-',
                        default => ApprovalStatus::options()[$state] ?? $state,
                    }),
                TextColumn::make('total_cost')->label('Total Nilai')->formatStateUsing(fn (mixed $state): string => IndoNumber::rupiah($state)),
                TextColumn::make('creator.name')->label('Dibuat Oleh')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ApprovalStatus::options()),
            ])
            ->recordActions([
                Action::make('submit')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (StockMovement $record): bool => $record->status === ApprovalStatus::Draft && Gate::allows('submit', $record))
                    ->action(function (StockMovement $record): void {
                        try {
                            app(StockMovementService::class)->submit($record);

                            Notification::make()
                                ->title('Pergerakan stok berhasil diajukan')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal mengajukan pergerakan stok')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (StockMovement $record): bool => $record->status === ApprovalStatus::Submitted && Gate::allows('approve', $record))
                    ->action(function (StockMovement $record): void {
                        $actor = Auth::user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        try {
                            app(StockMovementService::class)->approve($record, $actor);

                            Notification::make()
                                ->title('Pergerakan stok berhasil disetujui')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal menyetujui pergerakan stok')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (StockMovement $record): bool => $record->status === ApprovalStatus::Submitted && Gate::allows('reject', $record))
                    ->action(function (StockMovement $record): void {
                        $actor = Auth::user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        try {
                            app(StockMovementService::class)->reject($record, $actor);

                            Notification::make()
                                ->title('Pergerakan stok berhasil ditolak')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal menolak pergerakan stok')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
