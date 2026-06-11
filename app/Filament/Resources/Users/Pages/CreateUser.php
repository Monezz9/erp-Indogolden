<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $heading = 'Buat Pengguna';

    protected ?string $subheading = 'Tambahkan akun baru dan tentukan akses berdasarkan role.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back_to_users')
                ->label('Kembali ke Pengguna')
                ->icon('heroicon-o-arrow-left')
                ->outlined()
                ->color('gray')
                ->url(UserResource::getUrl('index')),
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.users.pages.user-management-header', [
            'title' => 'Buat Pengguna',
            'subtitle' => 'Tambahkan akun baru dan tentukan akses berdasarkan role.',
            'icon' => 'heroicon-o-user-plus',
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => Alignment::End,
            'breadcrumbs' => filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [],
        ]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan Pengguna')
            ->icon('heroicon-o-check-circle')
            ->color('warning');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Simpan & Buat Lagi')
            ->icon('heroicon-o-plus-circle')
            ->color('gray');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->outlined()
            ->color('gray');
    }
}
