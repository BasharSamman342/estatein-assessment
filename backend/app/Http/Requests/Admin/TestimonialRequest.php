<?php

namespace App\Http\Requests\Admin;

use App\Support\ImageRules;

class TestimonialRequest extends CmsRequest
{
    protected function fieldRules(): array
    {
        return [
            'quote_en' => ['required', 'string', 'max:2000'],
            'quote_ar' => ['required', 'string', 'max:2000'],
            'author_name_en' => ['required', 'string', 'max:120'],
            'author_name_ar' => ['required', 'string', 'max:120'],
            'role_en' => ['nullable', 'string', 'max:120'],
            'role_ar' => ['nullable', 'string', 'max:120'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'avatar' => ['nullable', ...ImageRules::upload()],
        ];
    }
}
