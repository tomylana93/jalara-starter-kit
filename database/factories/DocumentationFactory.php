<?php

namespace Database\Factories;

use App\Enums\DocumentationStatus;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Documentation>
 */
class DocumentationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documentation_category_id' => DocumentationCategory::factory(),
            'title' => $title = fake()->unique()->sentence(3),
            'slug' => str($title)->slug(),
            'status' => DocumentationStatus::Draft,
            'position' => fake()->numberBetween(0, 20),
            'content' => [
                'type' => 'doc',
                'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => fake()->sentence()]]]],
            ],
            'searchable_text' => fake()->sentence(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => DocumentationStatus::Published,
            'published_at' => now(),
        ]);
    }
}
