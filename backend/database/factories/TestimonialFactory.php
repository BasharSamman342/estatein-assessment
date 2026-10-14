<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Database\Factories\Concerns\ArabicText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    use ArabicText;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote_en' => fake()->paragraph(),
            'quote_ar' => $this->arabicParagraph(),
            'author_name_en' => fake()->name(),
            'author_name_ar' => $this->arabicWords(2),
            'role_en' => fake()->city().', '.fake()->country(),
            'role_ar' => $this->arabicWords(2),
            'rating' => fake()->numberBetween(1, 5),
            'avatar_path' => null,
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
