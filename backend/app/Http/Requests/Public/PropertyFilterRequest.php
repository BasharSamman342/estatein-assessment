<?php

namespace App\Http\Requests\Public;

use App\Enums\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyFilterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => [Rule::enum(Locale::class)],
        ];
    }

    public function locale(): Locale
    {
        return $this->enum('locale', Locale::class) ?? Locale::English;
    }
}
