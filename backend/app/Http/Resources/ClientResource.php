<?php

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;

/** @mixin Client */
class ClientResource extends CmsResource
{
    protected function fields(Request $request): array
    {
        return [
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'logo_url' => $this->imageUrl($this->logo_path),
        ];
    }
}
