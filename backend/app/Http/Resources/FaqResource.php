<?php

namespace App\Http\Resources;

use App\Models\Faq;
use Illuminate\Http\Request;

/** @mixin Faq */
class FaqResource extends CmsResource
{
    protected function fields(Request $request): array
    {
        return [
            'question_en' => $this->question_en,
            'question_ar' => $this->question_ar,
            'answer_en' => $this->answer_en,
            'answer_ar' => $this->answer_ar,
        ];
    }
}
