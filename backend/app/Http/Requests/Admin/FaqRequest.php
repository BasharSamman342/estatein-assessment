<?php

namespace App\Http\Requests\Admin;

class FaqRequest extends CmsRequest
{
    protected function fieldRules(): array
    {
        return [
            'question_en' => ['required', 'string', 'max:500'],
            'question_ar' => ['required', 'string', 'max:500'],
            'answer_en' => ['required', 'string', 'max:5000'],
            'answer_ar' => ['required', 'string', 'max:5000'],
        ];
    }
}
