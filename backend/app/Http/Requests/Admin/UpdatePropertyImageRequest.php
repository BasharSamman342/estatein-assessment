<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesBooleans;
use App\Models\PropertyImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePropertyImageRequest extends FormRequest
{
    use NormalizesBooleans;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_cover' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
            'alt_en' => ['sometimes', 'nullable', 'string', 'max:200'],
            'alt_ar' => ['sometimes', 'nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Clearing the flag would leave the property without a cover; the admin picks another image instead.
                if ($this->has('is_cover') && ! $this->boolean('is_cover') && $this->galleryImage()->is_cover) {
                    $validator->errors()->add('is_cover', 'Choose another image as the cover instead of removing it from this one.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleans(['is_cover']);
    }

    private function galleryImage(): PropertyImage
    {
        return $this->route('image');
    }
}
