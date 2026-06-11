<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_users')
                ->label('Kembali ke Pengguna')
                ->icon('heroicon-o-arrow-left')
                ->outlined()
                ->color('gray')
                ->url(UserResource::getUrl('index')),
            DeleteAction::make()
                ->successRedirectUrl(static::getResource()::getUrl('index')),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.users.pages.user-management-header', [
            'title' => 'Edit Pengguna',
            'subtitle' => 'Perbarui data akun, role, cabang, dan status akses pengguna.',
            'icon' => 'heroicon-o-user-circle',
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => Alignment::End,
            'breadcrumbs' => filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [],
        ]);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Pengguna')
            ->icon('heroicon-o-check-circle')
            ->color('warning');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->outlined()
            ->color('gray');
    }
}
