<?php

namespace App\Filament\Resources\BranchSales\Pages;

use App\Enums\BranchSaleStatus;
use App\Filament\Resources\BranchSales\BranchSaleResource;
use App\Models\User;
use App\Services\BranchSaleService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class EditBranchSale extends EditRecord
{
    protected static string $resource = BranchSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post')
                ->label('Post Nota')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === BranchSaleStatus::Draft && Gate::allows('post', $this->record))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(BranchSaleService::class)->post($this->record, $actor);

                        Notification::make()->title('Nota berhasil diposting')->success()->send();
                        $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record->fresh()]));
                    } catch (Throwable $exception) {
                        Notification::make()->title('Post nota gagal')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancel')
                ->label('Batalkan Draft')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === BranchSaleStatus::Draft && Gate::allows('cancel', $this->record))
                ->action(function (): void {
                    $actor = Auth::user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    try {
                        app(BranchSaleService::class)->cancelDraft($this->record, $actor);

                        Notification::make()->title('Draft nota dibatalkan')->success()->send();
                        $this->redirect(static::getResource()::getUrl('index'));
                    } catch (Throwable $exception) {
                        Notification::make()->title('Batal draft gagal')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('print_thermal')
                ->label('Print Thermal')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => route('branch-sales.print.thermal', ['branchSale' => $this->record]))
                ->openUrlInNewTab(),
            Action::make('print_a4')
                ->label('Print A4')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => route('branch-sales.print.a4', ['branchSale' => $this->record]))
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }

    protected function afterSave(): void
    {
        app(BranchSaleService::class)->syncTotals($this->record);
    }
}
