<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePropertyImageRequest;
use App\Http\Requests\Admin\UpdatePropertyImageRequest;
use App\Http\Resources\PropertyImageResource;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\PropertyImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Gallery of one property. Routes use scoped bindings, so an image id that belongs
 * to another property is a 404.
 */
class PropertyImageController extends Controller
{
    public function __construct(private readonly PropertyImageService $gallery) {}

    public function store(StorePropertyImageRequest $request, Property $property): JsonResponse
    {
        $image = $this->gallery->add($property, $request->file('image'), $request->safe()->except('image'));

        return (new PropertyImageResource($image))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdatePropertyImageRequest $request, Property $property, PropertyImage $image): PropertyImageResource
    {
        return new PropertyImageResource($this->gallery->update($image, $request->validated()));
    }

    public function destroy(Property $property, PropertyImage $image): Response
    {
        $this->gallery->delete($image);

        return response()->noContent();
    }
}
