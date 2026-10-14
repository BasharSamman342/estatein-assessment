<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Database\Factories\Concerns\ArabicText;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    use ArabicText;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->name(),
            'name_ar' => $this->arabicWords(2),
            'role_en' => fake()->jobTitle(),
            'role_ar' => $this->arabicWords(3),
            'bio_en' => null,
            'bio_ar' => null,
            'photo_path' => null,
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
