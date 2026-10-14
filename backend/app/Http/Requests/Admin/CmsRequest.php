<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesBooleans;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Base request for the admin create/update endpoints of the CMS sections.
 *
 * The same rules serve both: on create they apply as written, on update every
 * top-level field becomes optional ("sometimes"), so a partial PUT such as
 * {"is_published": true} only touches the fields it sends.
 */
abstract class CmsRequest extends FormRequest
{
    use NormalizesBooleans;

    /**
     * Rules for the section's own fields, written for a create.
     *
     * @return array<string, mixed>
     */
    abstract protected function fieldRules(): array;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            ...$this->fieldRules(),
            'sort_order' => ['integer', 'min:0', 'max:4294967295'],
            'is_published' => ['boolean'],
        ];

        return $this->isUpdate() ? $this->makePartial($rules) : $rules;
    }

    public function isUpdate(): bool
    {
        return $this->isMethod('PUT') || $this->isMethod('PATCH');
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeBooleans(['is_published']);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function makePartial(array $rules): array
    {
        foreach ($rules as $field => $fieldRules) {
            // Nested keys (features.*.label_en) are only checked when their parent is present anyway.
            if (! Str::contains($field, '.')) {
                $rules[$field] = ['sometimes', ...(array) $fieldRules];
            }
        }

        return $rules;
    }
}
