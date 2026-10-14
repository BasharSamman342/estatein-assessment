<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Manages a property's gallery and keeps its cover invariant: whenever a property has
 * images, exactly one of them is the cover.
 */
class PropertyImageService
{
    public function __construct(private readonly ImageStorage $storage) {}

    /**
     * Add an image. The property's first image becomes its cover; without a sort_order
     * the image goes to the end of the gallery.
     *
     * @param  array{alt_en?: string|null, alt_ar?: string|null, sort_order?: int|null}  $attributes
     */
    public function add(Property $property, UploadedFile $file, array $attributes = []): PropertyImage
    {
        $path = $this->storage->store($file, "properties/{$property->id}");

        try {
            return DB::transaction(function () use ($property, $path, $attributes) {
                $this->lock($property);

                $image = new PropertyImage(Arr::only($attributes, ['alt_en', 'alt_ar']));
                $image->path = $path;
                $image->sort_order = $attributes['sort_order'] ?? $this->nextSortOrder($property);
                $image->is_cover = ! $property->images()->where('is_cover', true)->exists();
                $property->images()->save($image);

                $this->normalizeCover($property);

                return $image->refresh();
            });
        } catch (Throwable $e) {
            $this->storage->delete($path);

            throw $e;
        }
    }

    /**
     * Update alt text, position or cover flag. Making an image the cover clears the flag
     * on its siblings in the same transaction.
     *
     * @param  array{alt_en?: string|null, alt_ar?: string|null, sort_order?: int, is_cover?: bool}  $attributes
     */
    public function update(PropertyImage $image, array $attributes): PropertyImage
    {
        return DB::transaction(function () use ($image, $attributes) {
            $property = $image->property;
            $this->lock($property);

            $image->fill(Arr::only($attributes, ['alt_en', 'alt_ar', 'sort_order']));

            if (filter_var($attributes['is_cover'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $property->images()->whereKeyNot($image->id)->where('is_cover', true)->update(['is_cover' => false]);
                $image->is_cover = true;
            }

            $image->save();
            $this->normalizeCover($property);

            return $image->refresh();
        });
    }

    /**
     * Delete an image (its file goes after commit). If it was the cover, the next image
     * by sort_order, then id, takes over.
     *
     * @throws ValidationException when it is the last image of a published property
     */
    public function delete(PropertyImage $image): void
    {
        DB::transaction(function () use ($image) {
            $property = $image->property;
            $this->lock($property);

            if ($property->is_published && $property->images()->count() === 1) {
                throw ValidationException::withMessages([
                    'image' => 'A published property must keep at least one image. Unpublish it first.',
                ]);
            }

            $image->delete();
            $this->normalizeCover($property);
        });
    }

    /**
     * Repair the cover flag: promote the first image when none is flagged, and keep only the
     * first flagged image (lowest sort_order, then id) when several are.
     */
    private function normalizeCover(Property $property): void
    {
        $covers = $property->images()->where('is_cover', true)->pluck('id');

        if ($covers->count() > 1) {
            $property->images()->whereIn('id', $covers->slice(1))->update(['is_cover' => false]);
        }

        if ($covers->isEmpty()) {
            $property->images()->limit(1)->update(['is_cover' => true]);
        }
    }

    private function nextSortOrder(Property $property): int
    {
        $max = $property->images()->max('sort_order');

        return $max === null ? 0 : $max + 1;
    }

    /**
     * Serialize gallery writes for one property, so two concurrent uploads cannot both become the cover.
     */
    private function lock(Property $property): void
    {
        Property::query()->whereKey($property->id)->lockForUpdate()->value('id');
    }
}
