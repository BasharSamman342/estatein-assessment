<?php

namespace App\Http\Requests\Admin;

use App\Support\ImageRules;

class ClientRequest extends CmsRequest
{
    protected function fieldRules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:120'],
            'name_ar' => ['required', 'string', 'max:120'],
            // Required on create; on update it becomes optional and the current logo is kept.
            'logo' => ['required', ...ImageRules::upload()],
        ];
    }
}
