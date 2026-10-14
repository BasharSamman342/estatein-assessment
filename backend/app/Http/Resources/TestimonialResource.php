<?php

namespace App\Http\Resources;

use App\Models\Testimonial;
use Illuminate\Http\Request;

/** @mixin Testimonial */
class TestimonialResource extends CmsResource
{
    protected function fields(Request $request): array
    {
        return [
            'quote_en' => $this->quote_en,
            'quote_ar' => $this->quote_ar,
            'author_name_en' => $this->author_name_en,
            'author_name_ar' => $this->author_name_ar,
            'role_en' => $this->role_en,
            'role_ar' => $this->role_ar,
            'rating' => $this->rating,
            'avatar_url' => $this->imageUrl($this->avatar_path),
        ];
    }
}
