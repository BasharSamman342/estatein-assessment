<?php

namespace App\Http\Requests\Admin;

use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PropertyRequest extends CmsRequest
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    protected function fieldRules(): array
    {
        return [
            // Optional: the server builds one from title_en when it is missing.
            'slug' => ['nullable', 'string', 'max:160', 'regex:'.self::SLUG_PATTERN, Rule::unique('properties', 'slug')->ignore($this->property()?->id)],
            'title_en' => ['required', 'string', 'max:200'],
            'title_ar' => ['required', 'string', 'max:200'],
            'description_en' => ['required', 'string', 'max:10000'],
            'description_ar' => ['required', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'currency' => ['string', 'size:3', 'alpha:ascii'],
            'location_en' => ['required', 'string', 'max:200'],
            'location_ar' => ['required', 'string', 'max:200'],
            'bedrooms' => ['required', 'integer', 'min:0', 'max:1000'],
            'bathrooms' => ['required', 'integer', 'min:0', 'max:1000'],
            'area_sqft' => ['required', 'integer', 'min:0', 'max:100000000'],
            'year_built' => ['required', 'integer', 'between:1800,2100'],
            'property_type' => ['required', Rule::enum(PropertyType::class)],
            // The full list; it replaces the property's current features.
            'features' => ['array', 'max:'.config('estatein.properties.max_features')],
            'features.*' => ['array:label_en,label_ar,sort_order'],
            'features.*.label_en' => ['required', 'string', 'max:120'],
            'features.*.label_ar' => ['required', 'string', 'max:120'],
            'features.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->boolean('is_published') && ! $this->property()?->images()->exists()) {
                    $validator->errors()->add('is_published', 'A property needs at least one image before it can be published.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_string($currency = $this->input('currency'))) {
            $this->merge(['currency' => strtoupper($currency)]);
        }
    }

    private function property(): ?Property
    {
        return $this->route('property');
    }
}
