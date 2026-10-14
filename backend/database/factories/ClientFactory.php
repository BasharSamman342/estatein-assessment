<?php

namespace Database\Factories;

use App\Models\Client;
use Database\Factories\Concerns\ArabicText;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    use ArabicText;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_en' => fake()->company(),
            'name_ar' => 'شركة '.$this->arabicWords(2),
            // Placeholder path: tests that care about the file upload a real one through the API.
            'logo_path' => 'clients/'.Str::random(40).'.png',
            'sort_order' => 0,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(['is_published' => true]);
    }
}
