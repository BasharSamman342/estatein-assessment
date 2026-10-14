<?php

namespace Database\Factories;

use App\Enums\PropertyType;
use App\Models\Property;
use Database\Factories\Concerns\ArabicText;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    use ArabicText;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'slug' => Str::slug($title),
            'title_en' => $title,
            'title_ar' => $this->arabicWords(3),
            'description_en' => fake()->paragraph(),
            'description_ar' => $this->arabicParagraph(),
            'price' => fake()->numberBetween(150, 3000) * 1000,
            'currency' => 'USD',
            'location_en' => fake()->city(),
            'location_ar' => $this->arabicWords(1),
            'bedrooms' => fake()->numberBetween(1, 6),
            'bathrooms' => fake()->numberBetween(1, 4),
            'area_sqft' => fake()->numberBetween(600, 6000),
            'year_built' => fake()->numberBetween(1970, 2025),
            'property_type' => fake()->randomElement(PropertyType::cases()),
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
