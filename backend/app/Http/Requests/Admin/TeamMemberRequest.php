<?php

namespace App\Http\Requests\Admin;

use App\Support\ImageRules;

class TeamMemberRequest extends CmsRequest
{
    protected function fieldRules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:120'],
            'name_ar' => ['required', 'string', 'max:120'],
            'role_en' => ['required', 'string', 'max:120'],
            'role_ar' => ['required', 'string', 'max:120'],
            'bio_en' => ['nullable', 'string', 'max:2000'],
            'bio_ar' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', ...ImageRules::upload()],
        ];
    }
}
