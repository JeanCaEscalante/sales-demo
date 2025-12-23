<?php

namespace Database\Factories;

use App\Enums\TypeDocument;
use App\Models\Contact;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type_document' => fake()->randomElement(TypeDocument::cases()),
            'document' => fake()->numerify('########'),
            'name' => fake()->company(),
            'address' => fake()->address(),
            'payment_terms' => fake()->randomElement(['Contado', '15 días', '30 días', '60 días']),
            'notes' => fake()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Supplier $supplier) {
            $supplier->contacts()->saveMany(
                Contact::factory()->count(rand(1, 3))->make()
            );
        });
    }
}
