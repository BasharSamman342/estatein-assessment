<?php

namespace App\Http\Resources;

use App\Models\TeamMember;
use Illuminate\Http\Request;

/** @mixin TeamMember */
class TeamMemberResource extends CmsResource
{
    protected function fields(Request $request): array
    {
        return [
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'role_en' => $this->role_en,
            'role_ar' => $this->role_ar,
            'bio_en' => $this->bio_en,
            'bio_ar' => $this->bio_ar,
            'photo_url' => $this->imageUrl($this->photo_path),
        ];
    }
}
