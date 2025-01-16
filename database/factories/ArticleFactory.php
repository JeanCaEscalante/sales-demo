<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::all()->random()->category_id,
            'code' => fake()->ean13(),
            'name' => fake()->sentence(3),
            'stock' => 0,
            'price_in' => 0,
            'price_out' => 0,
            'description' => fake()->text(),
        ];
    }
}
