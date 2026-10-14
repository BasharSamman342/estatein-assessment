<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use App\Services\ImageStorage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TestimonialController extends Controller
{
    public function __construct(private readonly ImageStorage $images) {}

    public function index(): AnonymousResourceCollection
    {
        return TestimonialResource::collection(Testimonial::query()->ordered()->get());
    }

    public function store(TestimonialRequest $request): TestimonialResource
    {
        return new TestimonialResource($this->save(new Testimonial, $request));
    }

    public function show(Testimonial $testimonial): TestimonialResource
    {
        return new TestimonialResource($testimonial);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        return new TestimonialResource($this->save($testimonial, $request));
    }

    public function destroy(Testimonial $testimonial): Response
    {
        $testimonial->delete();

        return response()->noContent();
    }

    private function save(Testimonial $testimonial, TestimonialRequest $request): Testimonial
    {
        return $this->images->saveWithImage(
            $testimonial,
            $request->safe()->except('avatar'),
            $request->file('avatar'),
            'avatar_path',
            'testimonials',
        );
    }
}
