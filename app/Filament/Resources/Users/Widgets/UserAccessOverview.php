<?php

namespace App\Filament\Resources\Users\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class UserAccessOverview extends Widget
{
    protected string $view = 'filament.resources.users.widgets.user-access-overview';

    protected int | string | array $columnSpan = 'full';

    /**
     * @return array<int, array{label: string, value: string, description: string, icon: string, tone: string}>
     */
    public function cards(): array
    {
        return [
            [
                'label' => 'Total Pengguna',
                'value' => (string) User::query()->count(),
                'description' => 'Semua akun sistem',
                'icon' => 'heroicon-o-users',
                'tone' => 'slate',
            ],
            [
                'label' => 'Pengguna Aktif',
                'value' => (string) User::query()->where('is_active', true)->count(),
                'description' => 'Bisa login panel',
                'icon' => 'heroicon-o-check-circle',
                'tone' => 'green',
            ],
            [
                'label' => 'Owner/Admin',
                'value' => (string) $this->roleCount([UserRole::Owner->value, UserRole::Admin->value]),
                'description' => 'Akses penuh',
                'icon' => 'heroicon-o-shield-check',
                'tone' => 'gold',
            ],
            [
                'label' => 'Finance',
                'value' => (string) $this->roleCount([UserRole::Finance->value]),
                'description' => 'Kas dan review PO',
                'icon' => 'heroicon-o-banknotes',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Gudang/Logistik',
                'value' => (string) $this->roleCount([
                    UserRole::Gudang->value,
                    UserRole::HeadLogistics->value,
                    UserRole::LogisticsAdmin->value,
                ]),
                'description' => 'Stok dan produksi',
                'icon' => 'heroicon-o-archive-box',
                'tone' => 'orange',
            ],
            [
                'label' => 'Cabang',
                'value' => (string) $this->roleCount([UserRole::Cabang->value, UserRole::Branch->value]),
                'description' => 'Request outlet',
                'icon' => 'heroicon-o-building-storefront',
                'tone' => 'blue',
            ],
        ];
    }

    /**
     * @param  array<int, string>  $roles
     */
    protected function roleCount(array $roles): int
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', $roles))
            ->count();
    }
}
