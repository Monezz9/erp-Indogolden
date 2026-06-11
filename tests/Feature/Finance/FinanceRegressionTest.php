<?php

namespace Tests\Feature\Finance;

use App\Enums\PaymentMethod;
use App\Filament\Pages\CashBook;
use App\Filament\Resources\FinanceExpenses\Pages\CreateFinanceExpense;
use App\Filament\Resources\FinanceIncomes\Pages\CreateFinanceIncome;
use App\Models\FinanceCategory;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinanceRegressionTest extends TestCase
{
    public function test_finance_amount_must_be_positive(): void
    {
        $this->prepareDatabase();
        $user = $this->createUserWithRole('finance');
        $this->actingAs($user);

        $incomeCategory = FinanceCategory::query()->create([
            'code' => 'REV-TEST',
            'name' => 'Pendapatan Test',
            'type' => 'income',
            'is_active' => true,
        ]);
        $expenseCategory = FinanceCategory::query()->create([
            'code' => 'OPEX-TEST',
            'name' => 'Biaya Test',
            'type' => 'expense',
            'is_active' => true,
        ]);

        Livewire::test(CreateFinanceIncome::class)
            ->fillForm($this->financePayload('INC-NEG', $incomeCategory->id, -10000))
            ->call('create')
            ->assertHasFormErrors(['amount' => ['min']]);

        Livewire::test(CreateFinanceIncome::class)
            ->fillForm($this->financePayload('INC-ZERO', $incomeCategory->id, 0))
            ->call('create')
            ->assertHasFormErrors(['amount' => ['min']]);

        Livewire::test(CreateFinanceIncome::class)
            ->fillForm($this->financePayload('INC-OK', $incomeCategory->id, 10000))
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateFinanceExpense::class)
            ->fillForm($this->financePayload('EXP-NEG', $expenseCategory->id, -10000))
            ->call('create')
            ->assertHasFormErrors(['amount' => ['min']]);

        Livewire::test(CreateFinanceExpense::class)
            ->fillForm($this->financePayload('EXP-ZERO', $expenseCategory->id, 0))
            ->call('create')
            ->assertHasFormErrors(['amount' => ['min']]);

        Livewire::test(CreateFinanceExpense::class)
            ->fillForm($this->financePayload('EXP-OK', $expenseCategory->id, 10000))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('finance_incomes', ['transaction_number' => 'INC-OK', 'amount' => 10000]);
        $this->assertDatabaseHas('finance_expenses', ['transaction_number' => 'EXP-OK', 'amount' => 10000]);
    }

    public function test_cash_book_date_filter_affects_rows_and_summary(): void
    {
        $this->prepareDatabase();
        $user = $this->createUserWithRole('finance');
        $this->actingAs($user);

        $incomeCategory = FinanceCategory::query()->create([
            'code' => 'REV-FILTER',
            'name' => 'Pendapatan Filter',
            'type' => 'income',
            'is_active' => true,
        ]);
        $expenseCategory = FinanceCategory::query()->create([
            'code' => 'OPEX-FILTER',
            'name' => 'Biaya Filter',
            'type' => 'expense',
            'is_active' => true,
        ]);

        FinanceIncome::query()->create($this->recordPayload('INC-JUN', $incomeCategory->id, 100000, '2026-06-05', $user->id));
        FinanceExpense::query()->create($this->recordPayload('EXP-JUN', $expenseCategory->id, 25000, '2026-06-06', $user->id));
        FinanceIncome::query()->create($this->recordPayload('INC-JUL', $incomeCategory->id, 50000, '2026-07-05', $user->id));

        $component = Livewire::test(CashBook::class)
            ->set('startDate', '2026-06-01')
            ->set('endDate', '2026-06-30');

        $summary = $component->instance()->summary();

        $this->assertSame(100000.0, $summary['income']);
        $this->assertSame(25000.0, $summary['expense']);
        $this->assertSame(75000.0, $summary['balance']);
        $this->assertCount(2, $component->instance()->rows());
    }

    /**
     * @return array<string, mixed>
     */
    protected function financePayload(string $number, int $categoryId, float $amount): array
    {
        return [
            'transaction_number' => $number,
            'transaction_date' => '2026-06-11 10:00:00',
            'finance_category_id' => $categoryId,
            'amount' => $amount,
            'payment_method' => PaymentMethod::Cash->value,
            'notes' => 'Testing nominal',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function recordPayload(string $number, int $categoryId, float $amount, string $date, int $userId): array
    {
        return [
            'transaction_number' => $number,
            'transaction_date' => $date.' 10:00:00',
            'finance_category_id' => $categoryId,
            'amount' => $amount,
            'payment_method' => PaymentMethod::Cash->value,
            'created_by' => $userId,
        ];
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
