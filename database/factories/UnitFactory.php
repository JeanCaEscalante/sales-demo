<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $units = [
            ['code' => 'UND', 'name' => 'Unidad'],
            ['code' => 'KG', 'name' => 'Kilogramo'],
            ['code' => 'LT', 'name' => 'Litro'],
            ['code' => 'MT', 'name' => 'Metro'],
            ['code' => 'CJ', 'name' => 'Caja'],
        ];

        return fake()->randomElement($units);
    }
}
