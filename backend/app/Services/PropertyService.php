<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyService
{
    public function __construct(private readonly ImageStorage $images) {}

    /**
     * @param  array<string, mixed>  $data  validated input
     */
    public function create(array $data): Property
    {
        return DB::transaction(function () use ($data) {
            $property = new Property(Arr::except($data, ['slug', 'features']));
            $property->slug = $data['slug'] ?? $this->uniqueSlug($data['title_en']);
            $property->save();

            $this->replaceFeatures($property, $data['features'] ?? []);

            return $property;
        });
    }

    /**
     * Partial update: features are replaced only when the key is sent, and the slug
     * stays the same when the title changes, so public links keep working.
     *
     * @param  array<string, mixed>  $data  validated input
     */
    public function update(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            $property->fill(Arr::except($data, ['slug', 'features']));

            if (filled($data['slug'] ?? null)) {
                $property->slug = $data['slug'];
            }

            $property->save();

            if (array_key_exists('features', $data)) {
                $this->replaceFeatures($property, $data['features']);
            }

            return $property;
        });
    }

    /**
     * Deleting the row cascades to images and features in the database, which fires no model
     * events, so the image paths are collected first and the files removed after commit.
     */
    public function delete(Property $property): void
    {
        $paths = $property->images()->pluck('path')->all();

        DB::transaction(function () use ($property, $paths) {
            $property->delete();
            $this->images->deleteAfterCommit($paths);
        });
    }

    /**
     * @param  array<int, array{label_en: string, label_ar: string, sort_order?: int|null}>  $features
     */
    private function replaceFeatures(Property $property, array $features): void
    {
        $property->features()->delete();

        $property->features()->createMany(array_map(
            fn (array $feature, int $index) => [
                'label_en' => $feature['label_en'],
                'label_ar' => $feature['label_ar'],
                'sort_order' => $feature['sort_order'] ?? $index,
            ],
            array_values($features),
            array_keys(array_values($features)),
        ));
    }

    /**
     * Str::slug(title_en), with -2, -3, … appended until it is free.
     */
    private function uniqueSlug(string $title): string
    {
        $base = rtrim(Str::substr(Str::slug($title), 0, 150), '-') ?: 'property';
        $slug = $base;

        for ($suffix = 2; Property::query()->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
