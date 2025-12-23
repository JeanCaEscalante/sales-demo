<?php

namespace Database\Factories;

use App\Enums\TypeDocument;
use App\Models\Contact;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

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
            'name' => fake()->name(),
            'address' => fake()->address(),
            'credit_limit' => fake()->randomFloat(2, 0, 10000),
            'notes' => fake()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Customer $customer) {
            $customer->contacts()->saveMany(
                Contact::factory()->count(rand(1, 3))->make()
            );
        });
    }
}
