<?php

namespace Tests\Feature\Access;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementAccessTest extends TestCase
{
    public function test_user_management_is_only_accessible_by_admin_and_owner(): void
    {
        $this->prepareDatabase();

        foreach (['owner', 'admin'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user);

            $this->assertTrue(UserResource::canAccess(), "Role {$role} seharusnya bisa akses menu Pengguna.");
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', User::class));
            $this->get('/admin/users')->assertOk();
            $this->get('/admin/users/'.$user->id.'/edit')->assertOk();
        }

        foreach (['finance', 'gudang', 'cabang', 'branch', 'head_logistics', 'logistics_admin'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user);

            $this->assertFalse(UserResource::canAccess(), "Role {$role} seharusnya tidak bisa akses menu Pengguna.");
            $this->assertFalse(Gate::forUser($user)->allows('viewAny', User::class));
            $this->get('/admin/users')->assertForbidden();
            $this->get('/admin/users/'.$user->id.'/edit')->assertForbidden();
        }
    }

    protected function createUserWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    protected function prepareDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Ekstensi pdo_sqlite belum tersedia pada environment ini.');
        }

        $this->artisan('migrate:fresh');
    }
}
