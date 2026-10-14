<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyFeature;
use Database\Factories\Concerns\ArabicText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyFeature>
 */
class PropertyFeatureFactory extends Factory
{
    use ArabicText;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'label_en' => fake()->sentence(4),
            'label_ar' => $this->arabicSentence(4),
            'sort_order' => 0,
        ];
    }
}
