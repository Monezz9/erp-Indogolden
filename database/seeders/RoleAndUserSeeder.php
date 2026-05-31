<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        $hq = Branch::query()->where('code', 'BR-HQ')->first();
        $candiPanggung = Branch::query()->where('code', 'BR-CPG')->first();
        $telukBayur = Branch::query()->where('code', 'BR-TBY')->first();

        $users = [
            [
                'name' => 'ERP Owner',
                'username' => 'owner',
                'email' => 'owner@erp.local',
                'roles' => [UserRole::Owner, UserRole::Admin],
                'branch_id' => $hq?->id,
            ],
            [
                'name' => 'ERP Finance',
                'username' => 'finance',
                'email' => 'finance@erp.local',
                'roles' => [UserRole::Finance],
                'branch_id' => $hq?->id,
            ],
            [
                'name' => 'Head Logistics',
                'username' => 'headlogistik',
                'email' => 'headlogistik@erp.local',
                'roles' => [UserRole::HeadLogistics, UserRole::Gudang],
                'branch_id' => $hq?->id,
            ],
            [
                'name' => 'Admin Logistics',
                'username' => 'adminlogistik',
                'email' => 'adminlogistik@erp.local',
                'roles' => [UserRole::LogisticsAdmin, UserRole::Gudang],
                'branch_id' => $hq?->id,
            ],
            [
                'name' => 'Cabang Candi Panggung',
                'username' => 'cabang.candipanggung',
                'email' => 'cabang.candipanggung@erp.local',
                'roles' => [UserRole::Branch, UserRole::Cabang],
                'branch_id' => $candiPanggung?->id,
            ],
            [
                'name' => 'Cabang Teluk Bayur',
                'username' => 'cabang.telukbayur',
                'email' => 'cabang.telukbayur@erp.local',
                'roles' => [UserRole::Branch, UserRole::Cabang],
                'branch_id' => $telukBayur?->id,
            ],
            [
                'name' => 'ERP Admin',
                'username' => 'admin',
                'email' => 'admin@erp.local',
                'roles' => [UserRole::Admin, UserRole::Owner],
                'branch_id' => $hq?->id,
            ],
            [
                'name' => 'ERP Gudang',
                'username' => 'gudang',
                'email' => 'gudang@erp.local',
                'roles' => [UserRole::Gudang, UserRole::LogisticsAdmin],
                'branch_id' => $hq?->id,
            ],
            [
                'name' => 'ERP Cabang General',
                'username' => 'cabang',
                'email' => 'cabang@erp.local',
                'roles' => [UserRole::Cabang, UserRole::Branch],
                'branch_id' => $candiPanggung?->id,
            ],
        ];

        foreach ($users as $entry) {
            $user = User::query()->updateOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'username' => $entry['username'],
                    'branch_id' => $entry['branch_id'],
                    'phone' => '08'.random_int(1000000000, 9999999999),
                    'is_active' => true,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $roles = collect($entry['roles'])
                ->map(fn (UserRole $role): string => $role->value)
                ->all();

            $user->syncRoles($roles);
        }
    }
}
