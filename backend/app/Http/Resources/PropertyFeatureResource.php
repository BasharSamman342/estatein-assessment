<?php

namespace App\Http\Resources;

use App\Models\PropertyFeature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropertyFeature */
class PropertyFeatureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label_en' => $this->label_en,
            'label_ar' => $this->label_ar,
            'sort_order' => $this->sort_order,
        ];
    }
}
