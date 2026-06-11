<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserAccessProfile
{
    public static function roleLabel(string $role): string
    {
        return match ($role) {
            UserRole::Owner->value => 'Owner',
            UserRole::Admin->value => 'Admin',
            UserRole::Finance->value => 'Finance',
            UserRole::Gudang->value => 'Gudang',
            UserRole::HeadLogistics->value => 'Head Logistics',
            UserRole::LogisticsAdmin->value => 'Logistics Admin',
            UserRole::Cabang->value => 'Cabang',
            UserRole::Branch->value => 'Branch',
            default => Str::headline($role),
        };
    }

    public static function roleDescription(string $role): string
    {
        return match ($role) {
            UserRole::Owner->value, UserRole::Admin->value => 'Akses penuh sistem dan pengaturan.',
            UserRole::Finance->value => 'Kelola finance, buku kas, dan review pengadaan.',
            UserRole::Gudang->value, UserRole::HeadLogistics->value, UserRole::LogisticsAdmin->value => 'Kelola stok, request, pengiriman, dan produksi.',
            UserRole::Cabang->value, UserRole::Branch->value => 'Buat request barang dan konfirmasi penerimaan.',
            default => 'Akses mengikuti konfigurasi role sistem.',
        };
    }

    public static function roleTone(string $role): string
    {
        return match ($role) {
            UserRole::Owner->value, UserRole::Admin->value => 'gold',
            UserRole::Finance->value => 'green',
            UserRole::Gudang->value, UserRole::HeadLogistics->value, UserRole::LogisticsAdmin->value => 'orange',
            UserRole::Cabang->value, UserRole::Branch->value => 'blue',
            default => 'gray',
        };
    }

    /**
     * @param  iterable<int, string>  $roles
     * @return array<string, bool>
     */
    public static function permissions(iterable $roles): array
    {
        $roles = collect($roles)->values();

        $isAdmin = $roles->intersect([UserRole::Owner->value, UserRole::Admin->value])->isNotEmpty();
        $isFinance = $roles->contains(UserRole::Finance->value);
        $isWarehouse = $roles->intersect([UserRole::Gudang->value, UserRole::HeadLogistics->value, UserRole::LogisticsAdmin->value])->isNotEmpty();
        $isBranch = $roles->intersect([UserRole::Cabang->value, UserRole::Branch->value])->isNotEmpty();

        return [
            'Dashboard' => $isAdmin || $isFinance || $isWarehouse || $isBranch,
            'Finance' => $isAdmin || $isFinance,
            'Gudang' => $isAdmin || $isWarehouse,
            'Produksi' => $isAdmin || $isWarehouse,
            'Request Cabang' => $isAdmin || $isWarehouse || $isBranch,
            'Pengaturan' => $isAdmin,
        ];
    }

    /**
     * @param  array<int, int|string>|null  $roleState
     * @return Collection<int, string>
     */
    public static function roleNamesFromState(?array $roleState): Collection
    {
        if (blank($roleState)) {
            return collect();
        }

        return Role::query()
            ->whereIn('id', array_map('intval', $roleState))
            ->pluck('name')
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    public static function roleNamesForUser(User $user): Collection
    {
        return $user->roles->pluck('name')->values();
    }
}
