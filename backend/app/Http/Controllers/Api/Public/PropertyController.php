<?php

namespace App\Http\Controllers\Api\Public;

use App\Filters\PropertyFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PropertyIndexRequest;
use App\Http\Resources\PropertyDetailResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(PropertyIndexRequest $request): AnonymousResourceCollection
    {
        $query = Property::query()->published()->with('coverImage');

        $properties = (new PropertyFilters($request->validated()))
            ->apply($query)
            ->ordered()
            ->paginate($request->perPage())
            ->withQueryString();

        return PropertyResource::collection($properties);
    }

    public function show(string $slug): PropertyDetailResource
    {
        $property = Property::query()
            ->published()
            ->where('slug', $slug)
            ->with(PropertyDetailResource::RELATIONS)
            ->firstOrFail();

        return new PropertyDetailResource($property);
    }
}
