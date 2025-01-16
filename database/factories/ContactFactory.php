<?php

namespace Database\Factories;

use App\Enums\TypeContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type_contact = $this->getResult(TypeContact::cases());

        return [
            'type_contact' => $type_contact,
            'contact' => match ($type_contact) {
                TypeContact::Email => fake()->email(),
                TypeContact::Phone => fake()->phoneNumber(),
            },
        ];
    }

    public function getResult($enums)
    {
        $i = rand(0, count($enums) - 1);

        return $enums[$i];
    }
}
