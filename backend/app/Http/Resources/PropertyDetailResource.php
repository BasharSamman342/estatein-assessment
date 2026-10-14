<?php

namespace App\Http\Resources;

use App\Models\Property;
use Illuminate\Http\Request;

/**
 * A single property: the list fields plus the full gallery and the key features.
 *
 * @mixin Property
 */
class PropertyDetailResource extends PropertyResource
{
    /**
     * Relations the detail payload needs; load them before building the resource.
     */
    public const RELATIONS = ['coverImage', 'images', 'features'];

    protected function fields(Request $request): array
    {
        return [
            ...parent::fields($request),
            'images' => PropertyImageResource::collection($this->images),
            'features' => PropertyFeatureResource::collection($this->features),
        ];
    }
}
