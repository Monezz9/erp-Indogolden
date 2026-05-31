<?php

namespace App\Filament\Resources\ProductionOrders\Pages;

use App\Enums\ProductionOrderStatus;
use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use App\Models\User;
use App\Services\ProductionService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class EditProductionOrder extends EditRecord
{
    protected static string $resource = ProductionOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === ProductionOrderStatus::Draft && Gate::allows('submit', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(ProductionService::class)->submitOrder($this->getRecord(), $actor);

                        Notification::make()->title('Production order berhasil disubmit')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Submit gagal')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === ProductionOrderStatus::Submitted && Gate::allows('approve', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(ProductionService::class)->approveOrder($this->getRecord(), $actor);

                        Notification::make()->title('Production order berhasil diapprove')->success()->send();
                        $this->refreshFormData(['status']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Approve gagal')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('complete')
                ->label('Complete')
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->getRecord()->status, [ProductionOrderStatus::Submitted, ProductionOrderStatus::Approved], true) && Gate::allows('complete', $this->getRecord()))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(ProductionService::class)->completeOrder(
                            $this->getRecord()->fresh(['inputs.item', 'outputs.item']),
                            $actor,
                            $this->getRecord()->warehouse_id,
                        );

                        Notification::make()->title('Production order berhasil diselesaikan')->success()->send();
                        $this->refreshFormData(['status', 'completed_at', 'actual_qty', 'total_input_cost', 'total_output_cost']);
                    } catch (Throwable $exception) {
                        Notification::make()->title('Complete gagal')->body($exception->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }
}
