<?php

namespace App\Http\Requests\Public;

use App\Enums\Locale;
use App\Enums\PriceRange;
use App\Enums\PropertyType;
use App\Enums\SizeRange;
use App\Enums\YearRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyIndexRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'between:1,'.config('estatein.properties.max_per_page')],
            'locale' => [Rule::enum(Locale::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', Rule::enum(PropertyType::class)],
            'price_range' => ['nullable', Rule::enum(PriceRange::class)],
            'size_range' => ['nullable', Rule::enum(SizeRange::class)],
            'year_range' => ['nullable', Rule::enum(YearRange::class)],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', config('estatein.properties.per_page'));
    }
}
