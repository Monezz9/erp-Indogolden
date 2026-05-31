<?php

namespace Database\Factories;

use App\Models\FinanceIncome;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceIncome>
 */
class FinanceIncomeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_number' => 'INC-'.now()->format('Ymd').'-'.fake()->numerify('###'),
            'transaction_date' => fake()->dateTimeBetween('-20 days'),
            'branch_id' => null,
            'finance_category_id' => 1,
            'amount' => fake()->randomFloat(2, 200000, 5000000),
            'payment_method' => fake()->randomElement(['cash', 'bank_transfer', 'qris']),
            'notes' => fake()->sentence(),
            'created_by' => 1,
        ];
    }
}
