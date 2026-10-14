<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use App\Services\ImageStorage;
use Database\Seeders\Support\Fixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class TestimonialSeeder extends Seeder
{
    public function run(ImageStorage $images): void
    {
        foreach (Fixtures::data('testimonials') as $order => $testimonial) {
            $images->saveWithImage(
                new Testimonial,
                [...Arr::except($testimonial, 'avatar'), 'sort_order' => $order],
                Fixtures::upload("avatars/{$testimonial['avatar']}"),
                'avatar_path',
                'testimonials',
            );
        }
    }
}
