<?php

namespace Tests\Feature\Access;

use App\Filament\Pages\CashBook;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashBookAccessTest extends TestCase
{
    public function test_cash_book_access_is_limited_to_owner_admin_and_finance(): void
    {
        $this->prepareDatabase();

        foreach (['owner', 'admin', 'finance'] as $role) {
            $this->actingAs($this->createUserWithRole($role));

            $this->assertTrue(CashBook::canAccess(), "Role {$role} seharusnya bisa akses Buku Kas.");
            $this->get('/admin/cash-book')->assertOk();
        }

        foreach (['cabang', 'branch', 'gudang', 'logistics_admin', 'head_logistics'] as $role) {
            $this->actingAs($this->createUserWithRole($role));

            $this->assertFalse(CashBook::canAccess(), "Role {$role} seharusnya tidak bisa akses Buku Kas.");
            $this->get('/admin/cash-book')->assertForbidden();
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
