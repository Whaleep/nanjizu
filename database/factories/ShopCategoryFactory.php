<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShopCategory>
 */
class ShopCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word . '系列',
            'slug' => $this->faker->unique()->slug,
            'is_visible' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'image' => 'https://loremflickr.com/320/240/model?lock=' . rand(1, 1000),
        ];
    }
}
