<?php

namespace App\Http\Requests\Public;

use App\Enums\InquiryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInquiryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->enum('type', InquiryType::class);

        return [
            'type' => ['required', Rule::enum(InquiryType::class)],
            // Required for a property inquiry and must point at a published property; not allowed on a contact message.
            'property_id' => [
                Rule::requiredIf($type === InquiryType::Property),
                Rule::prohibitedIf($type === InquiryType::Contact),
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where('is_published', true),
            ],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
