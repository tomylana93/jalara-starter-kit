<?php

namespace Database\Factories;

use App\Models\DocumentationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentationCategory>
 */
class DocumentationCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'position' => fake()->numberBetween(0, 20),
        ];
    }
}
