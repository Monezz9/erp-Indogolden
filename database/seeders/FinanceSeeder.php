<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\FinanceCategory;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['code' => 'REV-SALES', 'name' => 'Sales Revenue', 'type' => 'income', 'is_cogs' => false],
            ['code' => 'EXP-COGS', 'name' => 'COGS', 'type' => 'expense', 'is_cogs' => true],
            ['code' => 'EXP-OPEX', 'name' => 'Operational Expense', 'type' => 'expense', 'is_cogs' => false],
        ] as $category) {
            FinanceCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                $category + ['is_active' => true],
            );
        }

        $financeUser = User::query()->where('email', 'finance@erp.local')->first()
            ?? User::query()->where('email', 'owner@erp.local')->first();

        if (! $financeUser) {
            return;
        }

        $branches = Branch::query()->get();
        $incomeCategory = FinanceCategory::query()->where('code', 'REV-SALES')->firstOrFail();
        $cogsCategory = FinanceCategory::query()->where('code', 'EXP-COGS')->firstOrFail();
        $opexCategory = FinanceCategory::query()->where('code', 'EXP-OPEX')->firstOrFail();

        foreach ($branches as $idx => $branch) {
            for ($day = 0; $day < 7; $day++) {
                $date = now()->subDays($day);

                FinanceIncome::query()->updateOrCreate(
                    ['transaction_number' => 'INC-'.$branch->code.'-'.$date->format('Ymd')],
                    [
                        'transaction_date' => $date,
                        'branch_id' => $branch->id,
                        'finance_category_id' => $incomeCategory->id,
                        'amount' => 3_000_000 + (($idx + 1) * 250_000),
                        'payment_method' => 'cash',
                        'created_by' => $financeUser->id,
                        'notes' => 'Pendapatan harian cabang',
                    ],
                );

                FinanceExpense::query()->updateOrCreate(
                    ['transaction_number' => 'COGS-'.$branch->code.'-'.$date->format('Ymd')],
                    [
                        'transaction_date' => $date,
                        'branch_id' => $branch->id,
                        'finance_category_id' => $cogsCategory->id,
                        'amount' => 1_450_000 + (($idx + 1) * 175_000),
                        'payment_method' => 'cash',
                        'created_by' => $financeUser->id,
                        'notes' => 'COGS harian',
                    ],
                );

                FinanceExpense::query()->updateOrCreate(
                    ['transaction_number' => 'OPEX-'.$branch->code.'-'.$date->format('Ymd')],
                    [
                        'transaction_date' => $date,
                        'branch_id' => $branch->id,
                        'finance_category_id' => $opexCategory->id,
                        'amount' => 520_000 + (($idx + 1) * 90_000),
                        'payment_method' => 'bank_transfer',
                        'created_by' => $financeUser->id,
                        'notes' => 'Biaya operasional harian',
                    ],
                );
            }
        }
    }
}
