<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\UserAccessProfile;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'lg' => 3,
            ])
            ->components([
                Grid::make(1)
                    ->schema([
                        Section::make('Informasi Akun')
                            ->description('Identitas pengguna untuk login dan komunikasi internal.')
                            ->icon('heroicon-o-identification')
                            ->extraAttributes(['class' => 'ig-user-form-section'])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('username')
                                    ->label('Username')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->rule('regex:/^[a-z0-9._-]+$/')
                                    ->helperText('Gunakan huruf kecil, angka, titik, strip, atau underscore.')
                                    ->dehydrateStateUsing(fn (?string $state): string => strtolower(trim((string) $state)))
                                    ->live(onBlur: true),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->live(onBlur: true),
                                TextInput::make('phone')
                                    ->label('No. Telepon')
                                    ->tel()
                                    ->live(onBlur: true),
                                TextInput::make('password')
                                    ->label(fn (string $operation): string => $operation === 'create' ? 'Password' : 'Password Baru')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->helperText(fn (string $operation): string => $operation === 'create' ? 'Wajib diisi untuk akun baru.' : 'Kosongkan jika password tidak diubah.'),
                            ])
                            ->columns(2),
                        Section::make('Akses Sistem')
                            ->description('Tentukan area kerja dan role yang mengatur hak akses pengguna.')
                            ->icon('heroicon-o-shield-check')
                            ->extraAttributes(['class' => 'ig-user-form-section'])
                            ->schema([
                                Select::make('branch_id')
                                    ->label('Cabang')
                                    ->relationship('branch', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Head Office / Tidak terikat cabang')
                                    ->live(),
                                Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->helperText('Nonaktif berarti pengguna tidak dapat mengakses panel.')
                                    ->default(true)
                                    ->live(),
                                CheckboxList::make('roles')
                                    ->label('Role & Permission')
                                    ->relationship('roles', 'name')
                                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => UserAccessProfile::roleLabel($record->name))
                                    ->getOptionDescriptionFromRecordUsing(fn (Role $record): string => UserAccessProfile::roleDescription($record->name))
                                    ->columns(2)
                                    ->gridDirection('row')
                                    ->required()
                                    ->live()
                                    ->extraAttributes(['class' => 'ig-user-role-options'])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan([
                        'lg' => 2,
                    ]),
                Grid::make(1)
                    ->schema([
                        Section::make('Preview Pengguna')
                            ->description('Ringkasan akun sebelum disimpan.')
                            ->icon('heroicon-o-eye')
                            ->extraAttributes(['class' => 'ig-user-preview-section'])
                            ->schema([
                                Placeholder::make('preview')
                                    ->hiddenLabel()
                                    ->content(fn (Get $get): HtmlString => self::preview($get)),
                            ]),
                        Section::make('Ringkasan Hak Akses')
                            ->description('Checklist akses berdasarkan role yang dipilih.')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->extraAttributes(['class' => 'ig-user-preview-section'])
                            ->schema([
                                Placeholder::make('access_summary')
                                    ->hiddenLabel()
                                    ->content(fn (Get $get): HtmlString => self::accessSummary($get)),
                            ]),
                    ])
                    ->columnSpan([
                        'lg' => 1,
                    ]),
            ]);
    }

    protected static function preview(Get $get): HtmlString
    {
        $name = trim((string) $get('name')) ?: 'Nama Pengguna';
        $username = trim((string) $get('username')) ?: 'username';
        $email = trim((string) $get('email')) ?: 'email@erp.local';
        $branch = $get('branch_id') ? \App\Models\Branch::query()->find($get('branch_id'))?->name : 'Head Office';
        $roles = UserAccessProfile::roleNamesFromState($get('roles'));
        $initials = collect(explode(' ', $name))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->implode('') ?: 'U';

        $roleBadges = $roles->isNotEmpty()
            ? $roles->map(fn (string $role): string => sprintf(
                '<span class="ig-user-role-badge ig-user-role-badge--%s">%s</span>',
                e(UserAccessProfile::roleTone($role)),
                e(UserAccessProfile::roleLabel($role)),
            ))->implode('')
            : '<span class="ig-user-role-badge ig-user-role-badge--gray">Role belum dipilih</span>';

        return new HtmlString(sprintf(
            '<div class="ig-user-preview-card">
                <div class="ig-user-avatar ig-user-avatar--lg">%s</div>
                <div>
                    <div class="ig-user-preview-card__name">%s</div>
                    <div class="ig-user-preview-card__meta">@%s</div>
                    <div class="ig-user-preview-card__email">%s</div>
                </div>
                <dl>
                    <div><dt>Cabang / Area</dt><dd>%s</dd></div>
                    <div><dt>Status</dt><dd>%s</dd></div>
                </dl>
                <div class="ig-user-role-list">%s</div>
            </div>',
            e($initials),
            e($name),
            e($username),
            e($email),
            e($branch ?: 'Head Office'),
            $get('is_active') ? 'Aktif' : 'Nonaktif',
            $roleBadges,
        ));
    }

    protected static function accessSummary(Get $get): HtmlString
    {
        $roles = UserAccessProfile::roleNamesFromState($get('roles'));
        $permissions = UserAccessProfile::permissions($roles);

        $items = collect($permissions)
            ->map(fn (bool $allowed, string $label): string => sprintf(
                '<div class="%s"><span>%s</span><strong>%s</strong></div>',
                $allowed ? 'is-allowed' : 'is-muted',
                $allowed ? '&#10003;' : '-',
                e($label),
            ))
            ->implode('');

        return new HtmlString('<div class="ig-user-access-checklist">'.$items.'</div>');
    }
}
