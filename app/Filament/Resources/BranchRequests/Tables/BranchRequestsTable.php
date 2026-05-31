<?php

namespace App\Filament\Resources\BranchRequests\Tables;

use App\Enums\BranchRequestStatus;
use App\Models\BranchRequest;
use App\Models\User;
use App\Services\BranchRequestService;
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

class BranchRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')->label('No. Request')->searchable()->sortable(),
                TextColumn::make('branch.name')->label('Cabang')->searchable(),
                TextColumn::make('request_date')->label('Tanggal Request')->date('d M Y')->sortable(),
                TextColumn::make('delivery_date')->label('Tanggal Kirim')->date('d M Y')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (BranchRequestStatus|string|null $state): string => match (true) {
                        $state instanceof BranchRequestStatus => BranchRequestStatus::options()[$state->value] ?? $state->value,
                        $state === null || $state === '' => '-',
                        default => BranchRequestStatus::options()[$state] ?? $state,
                    }),
                TextColumn::make('items_count')->counts('items')->label('Jumlah Item'),
            ])
            ->filters([
                SelectFilter::make('status')->options(BranchRequestStatus::options()),
                SelectFilter::make('branch_id')->relationship('branch', 'name')->label('Cabang'),
            ])
            ->recordActions([
                Action::make('submit')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (BranchRequest $record): bool => Gate::allows('submit', $record))
                    ->action(fn (BranchRequest $record) => self::runStatusAction($record, 'submit')),
                Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (BranchRequest $record): bool => Gate::allows('review', $record))
                    ->action(fn (BranchRequest $record) => self::runStatusAction($record, 'review')),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BranchRequest $record): bool => Gate::allows('approve', $record))
                    ->action(fn (BranchRequest $record) => self::runStatusAction($record, 'approve')),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (BranchRequest $record): bool => Gate::allows('reject', $record))
                    ->action(fn (BranchRequest $record) => self::runStatusAction($record, 'reject')),
                Action::make('mark_packed')
                    ->label('Sudah Dipacking')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (BranchRequest $record): bool => Gate::allows('markPacked', $record))
                    ->action(fn (BranchRequest $record) => self::runStatusAction($record, 'markPacked')),
                Action::make('mark_shipped')
                    ->label('Sudah Dikirim')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (BranchRequest $record): bool => Gate::allows('markShipped', $record))
                    ->action(fn (BranchRequest $record) => self::runStatusAction($record, 'markShipped')),
                Action::make('mark_received')
                    ->label('Sudah Diterima')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (BranchRequest $record): bool => Gate::allows('markReceived', $record))
                    ->action(fn (BranchRequest $record) => self::runStatusAction($record, 'markReceived')),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function runStatusAction(BranchRequest $record, string $method): void
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            return;
        }

        try {
            app(BranchRequestService::class)->{$method}($record, $actor);

            Notification::make()
                ->title('Status request berhasil diperbarui')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Aksi gagal')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
