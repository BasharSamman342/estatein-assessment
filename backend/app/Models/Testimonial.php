<?php

namespace App\Models;

use App\Models\Concerns\HasStoredImages;
use App\Models\Concerns\Publishable;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'quote_en', 'quote_ar', 'author_name_en', 'author_name_ar', 'role_en', 'role_ar',
    'rating', 'sort_order', 'is_published',
])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, HasStoredImages, Publishable;

    public function imageColumns(): array
    {
        return ['avatar_path'];
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }
}
