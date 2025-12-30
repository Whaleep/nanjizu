<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['原廠', '副廠', '128G', '256G', '紅色', '黑色']),
            'price' => $this->faker->numberBetween(100, 20000),
            'stock' => $this->faker->numberBetween(0, 50),
            'sku' => strtoupper($this->faker->bothify('SKU-####-????')),
        ];
    }
}
