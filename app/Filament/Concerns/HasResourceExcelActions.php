<?php

namespace App\Filament\Concerns;

use App\Models\User;
use App\Support\Excel\ResourceExcelManager;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

trait HasResourceExcelActions
{
    /**
     * @return array<int, Action>
     */
    protected function getExcelHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $modelClass = static::getResource()::getModel();

                    return app(ResourceExcelManager::class)->exportTemplate($modelClass);
                }),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    try {
                        if (! app()->bound('excel')) {
                            throw new \RuntimeException('Paket Excel belum aktif. Jalankan composer dump-autoload lalu refresh browser.');
                        }

                        $modelClass = static::getResource()::getModel();
                        /** @var Builder $query */
                        $query = $this->getFilteredTableQuery() ?? static::getResource()::getEloquentQuery();

                        return app(ResourceExcelManager::class)->exportQuery(
                            modelClass: $modelClass,
                            query: $query,
                            filenamePrefix: class_basename($modelClass),
                        );
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Export gagal')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return null;
                    }
                }),

            Action::make('import_excel')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->visible(fn (): bool => Gate::allows('create', static::getResource()::getModel()))
                ->form([
                    Select::make('mode')
                        ->label('Mode Import')
                        ->required()
                        ->default('upsert')
                        ->options([
                            'insert_only' => 'Tambah data baru saja',
                            'update_existing' => 'Update data yang sudah ada',
                            'upsert' => 'Upsert',
                            'replace' => 'Ganti data',
                        ]),
                    FileUpload::make('file')
                        ->label('File Excel')
                        ->required()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'application/vnd.ms-excel',
                        ])
                        ->maxSize(10240)
                        ->directory('imports')
                        ->disk(config('filament.default_filesystem_disk', 'local')),
                ])
                ->action(function (array $data): void {
                    $user = Auth::user();

                    if (! $user instanceof User) {
                        return;
                    }

                    try {
                        $result = app(ResourceExcelManager::class)->importFromStoredFile(
                            modelClass: static::getResource()::getModel(),
                            storedPath: (string) $data['file'],
                            actor: $user,
                            mode: (string) ($data['mode'] ?? 'upsert'),
                        );

                        Notification::make()
                            ->title('Import selesai')
                            ->body(sprintf(
                                'Dibuat: %d | Diperbarui: %d | Dilewati: %d',
                                $result['created'],
                                $result['updated'],
                                $result['skipped'],
                            ))
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Import gagal')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
