<?php

namespace App\Filament\Resources\ProductionOrders\Tables;

use App\Enums\ProductionOrderStatus;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\ProductionService;
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

class ProductionOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('No. Order')->searchable()->sortable(),
                TextColumn::make('recipe.name')->label('Resep')->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ProductionOrderStatus|string|null $state): string => match (true) {
                        $state instanceof ProductionOrderStatus => ProductionOrderStatus::options()[$state->value] ?? $state->value,
                        $state === null || $state === '' => '-',
                        default => ProductionOrderStatus::options()[$state] ?? $state,
                    }),
                TextColumn::make('outputItem.name')->label('Barang Jadi')->searchable(),
                TextColumn::make('target_qty')->label('Target Qty')->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('actual_qty')->label('Qty Aktual')->formatStateUsing(fn (mixed $state): string => IndoNumber::decimal($state)),
                TextColumn::make('planned_date')->label('Tanggal Rencana')->date(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ProductionOrderStatus::options()),
            ])
            ->recordActions([
                Action::make('submit')
                    ->label('Ajukan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionOrder $record): bool => $record->status === ProductionOrderStatus::Draft && Gate::allows('submit', $record))
                    ->action(function (ProductionOrder $record): void {
                        $actor = Auth::user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        try {
                            app(ProductionService::class)->submitOrder($record, $actor);

                            Notification::make()
                                ->title('Order produksi berhasil diajukan')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal mengajukan order produksi')
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
                    ->visible(fn (ProductionOrder $record): bool => $record->status === ProductionOrderStatus::Submitted && Gate::allows('approve', $record))
                    ->action(function (ProductionOrder $record): void {
                        $actor = Auth::user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        try {
                            app(ProductionService::class)->approveOrder($record, $actor);

                            Notification::make()
                                ->title('Order produksi berhasil disetujui')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal menyetujui order produksi')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('complete')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-badge')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ProductionOrder $record): bool => in_array($record->status, [ProductionOrderStatus::Submitted, ProductionOrderStatus::Approved], true) && Gate::allows('complete', $record))
                    ->action(function (ProductionOrder $record): void {
                        $actor = Auth::user();

                        if (! $actor instanceof User) {
                            return;
                        }

                        try {
                            app(ProductionService::class)->completeOrder($record->fresh(['inputs.item', 'outputs.item']), $actor, $record->warehouse_id);

                            Notification::make()
                                ->title('Production order berhasil diselesaikan')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Gagal menyelesaikan order produksi')
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
