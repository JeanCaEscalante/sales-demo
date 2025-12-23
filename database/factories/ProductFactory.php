<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'code' => fake()->unique()->ean13(),
            'name' => fake()->words(3, true),
            'stock' => fake()->numberBetween(0, 100),
            'price_in' => fake()->randomFloat(2, 1, 100),
            'price_out' => fake()->randomFloat(2, 101, 200),
            'description' => fake()->paragraph(),
        ];
    }
}
