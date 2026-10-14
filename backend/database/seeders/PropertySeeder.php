<?php

namespace Database\Seeders;

use App\Services\PropertyImageService;
use App\Services\PropertyService;
use Database\Seeders\Support\Fixtures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class PropertySeeder extends Seeder
{
    public function run(PropertyService $properties, PropertyImageService $gallery): void
    {
        foreach (Fixtures::data('properties') as $order => $data) {
            // Same path as the admin: create a draft, upload the gallery, then publish.
            $property = $properties->create([
                ...Arr::except($data, ['features', 'images', 'is_published']),
                'sort_order' => $order,
                'features' => array_map(fn (array $feature) => ['label_en' => $feature[0], 'label_ar' => $feature[1]], $data['features']),
            ]);

            foreach ($data['images'] as $number => $file) {
                $gallery->add($property, Fixtures::upload("properties/{$file}"), [
                    'alt_en' => "{$data['title_en']}, photo ".($number + 1),
                    'alt_ar' => "{$data['title_ar']}، الصورة ".($number + 1),
                ]);
            }

            if ($data['is_published']) {
                $properties->update($property, ['is_published' => true]);
            }
        }
    }
}
