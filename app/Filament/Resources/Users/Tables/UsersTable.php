<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Branch;
use App\Support\UserAccessProfile;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari nama, username, atau email...')
            ->persistSearchInSession()
            ->columns([
                ViewColumn::make('name')
                    ->label('Pengguna')
                    ->view('filament.resources.users.tables.columns.user-profile')
                    ->searchable(['name', 'username', 'email'])
                    ->sortable()
                    ->width('330px'),
                TextColumn::make('branch.name')
                    ->label('Cabang / Area')
                    ->placeholder('Head Office')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                ViewColumn::make('roles')
                    ->label('Role & Permission')
                    ->view('filament.resources.users.tables.columns.role-badges'),
                ViewColumn::make('is_active')
                    ->label('Status')
                    ->view('filament.resources.users.tables.columns.access-status')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn (): array => Role::query()
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->map(fn (string $role): string => UserAccessProfile::roleLabel($role))
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->whereHas('roles', fn (Builder $roleQuery): Builder => $roleQuery->where('name', $data['value']))),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(fn (): array => Branch::query()->orderBy('name')->pluck('name', 'id')->all()),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->native(false),
            ])
            ->recordActions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->outlined()
                    ->color('info')
                    ->modalSubmitAction(false)
                    ->modalWidth('lg')
                    ->modalContent(fn ($record) => view('filament.resources.users.tables.actions.user-detail', ['record' => $record])),
                EditAction::make()
                    ->label('Edit')
                    ->iconButton()
                    ->outlined()
                    ->color('warning'),
                Action::make('toggle_active')
                    ->label(fn ($record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn ($record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
                    ->iconButton()
                    ->outlined()
                    ->color(fn ($record): string => $record->is_active ? 'gray' : 'success')
                    ->requiresConfirmation()
                    ->action(fn ($record): bool => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Tambahkan akun pertama untuk mulai mengatur akses sistem.')
            ->emptyStateActions([
                Action::make('create_user')
                    ->label('Buat Pengguna')
                    ->icon('heroicon-o-plus')
                    ->color('warning')
                    ->url(fn (): string => \App\Filament\Resources\Users\UserResource::getUrl('create')),
            ]);
    }
}
