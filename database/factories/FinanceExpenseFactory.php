<?php

namespace Database\Factories;

use App\Models\FinanceExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceExpense>
 */
class FinanceExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_number' => 'EXP-'.now()->format('Ymd').'-'.fake()->numerify('###'),
            'transaction_date' => fake()->dateTimeBetween('-20 days'),
            'branch_id' => null,
            'supplier_id' => null,
            'finance_category_id' => 2,
            'amount' => fake()->randomFloat(2, 50000, 3000000),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'qris']),
            'notes' => fake()->sentence(),
            'created_by' => 1,
        ];
    }
}
