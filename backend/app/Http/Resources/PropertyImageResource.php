<?php

namespace App\Http\Resources;

use App\Models\PropertyImage;
use App\Services\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropertyImage */
class PropertyImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => app(ImageStorage::class)->url($this->path),
            'alt_en' => $this->alt_en,
            'alt_ar' => $this->alt_ar,
            'is_cover' => $this->is_cover,
            'sort_order' => $this->sort_order,
        ];
    }
}
