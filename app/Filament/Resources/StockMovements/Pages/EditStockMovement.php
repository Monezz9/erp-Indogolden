<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Enums\ApprovalStatus;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\User;
use App\Services\StockMovementService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class EditStockMovement extends EditRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === ApprovalStatus::Draft && Gate::allows('submit', $this->getRecord()))
                ->action(function (): void {
                    try {
                        app(StockMovementService::class)->submit($this->getRecord());

                        Notification::make()->title('Pergerakan stok berhasil diajukan')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal mengajukan pergerakan stok')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === ApprovalStatus::Submitted && Gate::allows('approve', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(StockMovementService::class)->approve($this->getRecord(), $actor);

                        Notification::make()->title('Pergerakan stok berhasil disetujui')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal menyetujui pergerakan stok')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === ApprovalStatus::Submitted && Gate::allows('reject', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(StockMovementService::class)->reject($this->getRecord(), $actor);

                        Notification::make()->title('Pergerakan stok berhasil ditolak')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal menolak pergerakan stok')->body($exception->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }
}
