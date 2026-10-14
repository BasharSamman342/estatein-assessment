<?php

namespace App\Http\Resources;

use App\Models\Property;
use Illuminate\Http\Request;

/**
 * A property as it appears in lists: no gallery and no features, only the cover image.
 *
 * @mixin Property
 */
class PropertyResource extends CmsResource
{
    protected function fields(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'price' => $this->price,
            'currency' => $this->currency,
            'location_en' => $this->location_en,
            'location_ar' => $this->location_ar,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area_sqft' => $this->area_sqft,
            'year_built' => $this->year_built,
            'property_type' => $this->property_type->value,
            'cover_image' => $this->coverImage ? [
                'id' => $this->coverImage->id,
                'url' => $this->imageUrl($this->coverImage->path),
                'alt_en' => $this->coverImage->alt_en,
                'alt_ar' => $this->coverImage->alt_ar,
            ] : null,
        ];
    }
}
