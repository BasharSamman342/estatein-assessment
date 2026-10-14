<?php

namespace Database\Factories;

use App\Models\Faq;
use Database\Factories\Concerns\ArabicText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    use ArabicText;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_en' => rtrim(fake()->sentence(8), '.').'?',
            'question_ar' => $this->arabicWords(7).'؟',
            'answer_en' => fake()->paragraph(),
            'answer_ar' => $this->arabicParagraph(),
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
