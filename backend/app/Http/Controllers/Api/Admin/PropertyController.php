<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyRequest;
use App\Http\Resources\PropertyDetailResource;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PropertyController extends Controller
{
    public function __construct(private readonly PropertyService $properties) {}

    public function index(): AnonymousResourceCollection
    {
        return PropertyResource::collection(Property::query()->with('coverImage')->ordered()->get());
    }

    public function store(PropertyRequest $request): PropertyDetailResource
    {
        $property = $this->properties->create($request->validated());

        return new PropertyDetailResource($property->load(PropertyDetailResource::RELATIONS));
    }

    public function show(Property $property): PropertyDetailResource
    {
        return new PropertyDetailResource($property->load(PropertyDetailResource::RELATIONS));
    }

    public function update(PropertyRequest $request, Property $property): PropertyDetailResource
    {
        $property = $this->properties->update($property, $request->validated());

        return new PropertyDetailResource($property->load(PropertyDetailResource::RELATIONS));
    }

    public function destroy(Property $property): Response
    {
        $this->properties->delete($property);

        return response()->noContent();
    }
}
