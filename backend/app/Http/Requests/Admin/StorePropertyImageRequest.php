<?php

namespace App\Http\Requests\Admin;

use App\Models\Property;
use App\Support\ImageRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePropertyImageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', ...ImageRules::upload()],
            'alt_en' => ['nullable', 'string', 'max:200'],
            'alt_ar' => ['nullable', 'string', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $limit = config('estatein.images.max_per_property');

                if ($this->property()->images()->count() >= $limit) {
                    $validator->errors()->add('image', "A property can have at most {$limit} images.");
                }
            },
        ];
    }

    private function property(): Property
    {
        return $this->route('property');
    }
}
