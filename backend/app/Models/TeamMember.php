<?php

namespace App\Models;

use App\Models\Concerns\HasStoredImages;
use App\Models\Concerns\Publishable;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name_en', 'name_ar', 'role_en', 'role_ar', 'bio_en', 'bio_ar', 'sort_order', 'is_published'])]
class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory, HasStoredImages, Publishable;

    public function imageColumns(): array
    {
        return ['photo_path'];
    }
}
