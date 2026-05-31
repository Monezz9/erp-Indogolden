<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->bothify('SKU-#####')),
            'name' => fake()->words(2, true),
            'item_category_id' => 1,
            'default_unit_id' => 1,
            'default_stage_id' => 1,
            'item_type' => fake()->randomElement(['material', 'semi_finished', 'product', 'packaging']),
            'requires_production' => fake()->boolean(25),
            'is_perishable' => fake()->boolean(20),
            'minimum_stock' => fake()->randomFloat(2, 0, 200),
            'latest_weighted_avg_cost' => fake()->randomFloat(2, 1000, 100000),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
