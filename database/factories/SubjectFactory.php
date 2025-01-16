<?php

namespace Database\Factories;

use App\Enums\TypeDocument;
use App\Enums\TypeSubject;
use App\Models\Contact;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type_subject = $this->getResult(TypeSubject::cases());
        $type_document = $this->getResult(TypeDocument::cases());

        return [
            'type_subject' => $type_subject,
            'type_document' => $type_document,
            'document' => fake()->numerify('########'),
            'name' => fake()->name(),
            'address' => fake()->address(),
        ];
    }

    public function getResult($enums)
    {
        $i = rand(0, count($enums) - 1);

        return $enums[$i];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Subject $subject) {

            $subject->contacts()->saveMany(
                Contact::factory()->count(4)->make()
            );
        });
    }
}
