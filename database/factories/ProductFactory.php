<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper($this->faker->unique()->bothify('???-###')),
            'name' => ucfirst($this->faker->words(3, true)),
            'unit_code' => 'NIU',
            'stock' => $this->faker->numberBetween(10, 500),
            'is_active' => true,
        ];
    }
}
