<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Concerns\HasResourceExcelActions;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UserAccessOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\View\View;

class ListUsers extends ListRecords
{
    use HasResourceExcelActions;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        $excelActions = $this->getExcelHeaderActions();

        foreach ($excelActions as $action) {
            if ($action->getName() === 'import_excel') {
                $action->outlined()->color('warning');
            }

            if ($action->getName() === 'export_excel') {
                $action->outlined()->color('gray');
            }
        }

        return [
            CreateAction::make()
                ->label('Buat Pengguna')
                ->icon('heroicon-o-plus')
                ->color('warning'),
            ...$excelActions,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserAccessOverview::class,
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.users.pages.user-management-header', [
            'title' => 'Manajemen Pengguna',
            'subtitle' => 'Kelola akun, role, cabang, dan status akses pengguna INDOGOLDEN.',
            'icon' => 'heroicon-o-users',
            'actions' => $this->getCachedHeaderActions(),
            'actionsAlignment' => Alignment::End,
            'breadcrumbs' => filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [],
        ]);
    }
}
