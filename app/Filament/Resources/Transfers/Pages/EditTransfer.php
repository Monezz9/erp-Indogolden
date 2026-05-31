<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Enums\TransferStatus;
use App\Filament\Resources\Transfers\TransferResource;
use App\Models\User;
use App\Services\TransferService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class EditTransfer extends EditRecord
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Ajukan')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === TransferStatus::Draft && Gate::allows('submit', $this->getRecord()))
                ->action(function (): void {
                    try {
                        app(TransferService::class)->submit($this->getRecord());

                        Notification::make()->title('Transfer berhasil diajukan')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal mengajukan transfer')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === TransferStatus::Submitted && Gate::allows('approve', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(TransferService::class)->approve($this->getRecord(), $actor);

                        Notification::make()->title('Transfer berhasil disetujui')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal menyetujui transfer')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === TransferStatus::Submitted && Gate::allows('reject', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(TransferService::class)->reject($this->getRecord(), $actor);

                        Notification::make()->title('Transfer berhasil ditolak')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal menolak transfer')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('ship')
                ->label('Kirim')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === TransferStatus::Approved && Gate::allows('ship', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(TransferService::class)->ship($this->getRecord(), $actor);

                        Notification::make()->title('Transfer berhasil dikirim')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal mengirim transfer')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('receive')
                ->label('Terima')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === TransferStatus::Shipped && Gate::allows('receive', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(TransferService::class)->receive($this->getRecord(), $actor);

                        Notification::make()->title('Transfer berhasil diterima')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Gagal menerima transfer')->body($exception->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }
}
