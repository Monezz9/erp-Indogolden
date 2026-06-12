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
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class BranchRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(fn (): \Illuminate\Contracts\View\View => view('filament.resources.branch-requests.tables.header-summary', [
                'stats' => self::summaryStats(),
            ]))
            ->searchPlaceholder('Cari no request atau cabang...')
            ->persistSearchInSession()
            ->columns([
                ViewColumn::make('request_number')
                    ->label('Request')
                    ->view('filament.resources.branch-requests.tables.columns.request-summary')
                    ->searchable()
                    ->sortable()
                    ->width('240px'),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('delivery_date')
                    ->label('Tanggal Kirim')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (BranchRequestStatus|string|null $state): string => match (true) {
                        $state instanceof BranchRequestStatus => BranchRequestStatus::options()[$state->value] ?? $state->value,
                        $state === null || $state === '' => '-',
                        default => BranchRequestStatus::options()[$state] ?? $state,
                    })
                    ->color(fn (BranchRequestStatus|string|null $state): string => self::statusColor($state)),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Jumlah Item')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),
                ViewColumn::make('progress')
                    ->label('Progress')
                    ->view('filament.resources.branch-requests.tables.columns.request-progress')
                    ->width('360px'),
            ])
            ->filters([
                SelectFilter::make('status')->options(BranchRequestStatus::options()),
                SelectFilter::make('branch_id')->relationship('branch', 'name')->label('Cabang'),
                Filter::make('delivery_date')
                    ->label('Tanggal Kirim')
                    ->schema([
                        DatePicker::make('delivery_from')->label('Dari Tanggal'),
                        DatePicker::make('delivery_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['delivery_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('delivery_date', '>=', $date))
                            ->when($data['delivery_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('delivery_date', '<=', $date));
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
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
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('Belum ada request barang cabang')
            ->emptyStateDescription('Buat request pertama untuk mengajukan kebutuhan barang jadi ke gudang.')
            ->emptyStateActions([
                Action::make('create_request')
                    ->label('Buat Request')
                    ->icon('heroicon-o-plus')
                    ->color('danger')
                    ->url(fn (): string => \App\Filament\Resources\BranchRequests\BranchRequestResource::getUrl('create')),
            ]);
    }

    /**
     * @return array<int, array{label: string, value: int, icon: string, tone: string}>
     */
    protected static function summaryStats(): array
    {
        $query = \App\Filament\Resources\BranchRequests\BranchRequestResource::getEloquentQuery();

        return [
            [
                'label' => 'Total Request',
                'value' => (clone $query)->count(),
                'icon' => 'heroicon-o-clipboard-document-list',
                'tone' => 'slate',
            ],
            [
                'label' => 'Menunggu Review',
                'value' => (clone $query)->whereIn('status', [
                    BranchRequestStatus::Submitted->value,
                    BranchRequestStatus::Reviewed->value,
                ])->count(),
                'icon' => 'heroicon-o-clock',
                'tone' => 'orange',
            ],
            [
                'label' => 'Disetujui',
                'value' => (clone $query)->where('status', BranchRequestStatus::Approved->value)->count(),
                'icon' => 'heroicon-o-check-circle',
                'tone' => 'blue',
            ],
            [
                'label' => 'Dikirim',
                'value' => (clone $query)->where('status', BranchRequestStatus::Shipped->value)->count(),
                'icon' => 'heroicon-o-truck',
                'tone' => 'amber',
            ],
            [
                'label' => 'Diterima',
                'value' => (clone $query)->where('status', BranchRequestStatus::Received->value)->count(),
                'icon' => 'heroicon-o-inbox-arrow-down',
                'tone' => 'green',
            ],
        ];
    }

    public static function statusColor(BranchRequestStatus|string|null $state): string
    {
        $value = $state instanceof BranchRequestStatus ? $state->value : $state;

        return match ($value) {
            BranchRequestStatus::Submitted->value => 'warning',
            BranchRequestStatus::Reviewed->value => 'warning',
            BranchRequestStatus::Approved->value => 'info',
            BranchRequestStatus::Packed->value => 'purple',
            BranchRequestStatus::Shipped->value => 'warning',
            BranchRequestStatus::Received->value => 'success',
            BranchRequestStatus::Rejected->value => 'danger',
            default => 'gray',
        };
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
