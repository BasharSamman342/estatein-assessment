<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyAdminTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = [
        'title_en' => 'Seaside Serenity Villa',
        'title_ar' => 'فيلا سيسايد سيرينيتي',
        'description_en' => 'A stunning 4-bedroom villa with ocean views.',
        'description_ar' => 'فيلا مذهلة من 4 غرف نوم بإطلالة على المحيط.',
        'price' => 1250000,
        'currency' => 'usd',
        'location_en' => 'Malibu, California',
        'location_ar' => 'ماليبو، كاليفورنيا',
        'bedrooms' => 4,
        'bathrooms' => 3,
        'area_sqft' => 2500,
        'year_built' => 2019,
        'property_type' => 'villa',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAsAdmin();
    }

    public function test_create_returns_the_detail_payload_as_a_draft(): void
    {
        $this->postJson('/api/admin/properties', [
            ...self::VALID,
            'features' => [
                ['label_en' => 'Oceanfront terrace', 'label_ar' => 'شرفة مطلة على المحيط'],
                ['label_en' => 'Private pool', 'label_ar' => 'مسبح خاص'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'seaside-serenity-villa')
            ->assertJsonPath('data.price', '1250000.00')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.is_published', false)
            ->assertJsonPath('data.cover_image', null)
            ->assertJsonPath('data.images', [])
            ->assertJsonPath('data.features.*.label_en', ['Oceanfront terrace', 'Private pool'])
            ->assertJsonPath('data.features.*.sort_order', [0, 1]);

        $this->assertDatabaseHas('properties', ['slug' => 'seaside-serenity-villa', 'title_ar' => self::VALID['title_ar'], 'currency' => 'USD']);
    }

    public function test_currency_defaults_to_usd(): void
    {
        $data = self::VALID;
        unset($data['currency']);

        $this->postJson('/api/admin/properties', $data)->assertCreated()->assertJsonPath('data.currency', 'USD');
    }

    public function test_create_without_title_en_is_rejected(): void
    {
        $this->postJson('/api/admin/properties', [...self::VALID, 'title_en' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title_en']);

        $this->assertDatabaseCount('properties', 0);
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->postJson('/api/admin/properties', [
            ...self::VALID,
            'price' => -1,
            'bedrooms' => 'many',
            'year_built' => 1700,
            'property_type' => 'castle',
            'currency' => 'US',
        ])->assertUnprocessable()->assertJsonValidationErrors(['price', 'bedrooms', 'year_built', 'property_type', 'currency']);

        $this->postJson('/api/admin/properties', [...self::VALID, 'price' => 10.555])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_features_are_validated_and_capped_at_thirty(): void
    {
        $this->postJson('/api/admin/properties', [...self::VALID, 'features' => [['label_en' => 'Pool']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['features.0.label_ar']);

        $features = array_fill(0, 31, ['label_en' => 'Pool', 'label_ar' => 'مسبح']);

        $this->postJson('/api/admin/properties', [...self::VALID, 'features' => $features])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['features']);
    }

    public function test_slug_collisions_get_a_numeric_suffix(): void
    {
        $this->postJson('/api/admin/properties', self::VALID)->assertCreated()->assertJsonPath('data.slug', 'seaside-serenity-villa');
        $this->postJson('/api/admin/properties', self::VALID)->assertCreated()->assertJsonPath('data.slug', 'seaside-serenity-villa-2');
        $this->postJson('/api/admin/properties', self::VALID)->assertCreated()->assertJsonPath('data.slug', 'seaside-serenity-villa-3');
    }

    public function test_a_custom_slug_must_match_the_pattern_and_be_unique(): void
    {
        $this->postJson('/api/admin/properties', [...self::VALID, 'slug' => 'my-villa'])->assertCreated()->assertJsonPath('data.slug', 'my-villa');

        $this->postJson('/api/admin/properties', [...self::VALID, 'slug' => 'my-villa'])
            ->assertUnprocessable()->assertJsonValidationErrors(['slug']);

        foreach (['My Villa', 'villa_1', '-villa', 'villa--1'] as $invalid) {
            $this->postJson('/api/admin/properties', [...self::VALID, 'slug' => $invalid])
                ->assertUnprocessable()->assertJsonValidationErrors(['slug']);
        }
    }

    public function test_update_keeps_the_slug_when_the_title_changes(): void
    {
        $property = Property::factory()->create(['slug' => 'original-slug']);

        $this->putJson("/api/admin/properties/{$property->id}", [...self::VALID, 'title_en' => 'A New Title'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'original-slug')
            ->assertJsonPath('data.title_en', 'A New Title');

        // Re-sending its own slug is not a collision.
        $this->putJson("/api/admin/properties/{$property->id}", ['slug' => 'original-slug'])->assertOk();
    }

    public function test_publishing_without_images_is_rejected(): void
    {
        $this->postJson('/api/admin/properties', [...self::VALID, 'is_published' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_published']);

        $property = Property::factory()->create();

        $this->putJson("/api/admin/properties/{$property->id}", ['is_published' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_published']);
        $this->assertFalse($property->refresh()->is_published);
    }

    public function test_publishing_with_an_image_succeeds_and_unpublishing_needs_none(): void
    {
        $property = Property::factory()->create();
        PropertyImage::factory()->cover()->for($property)->create();

        $this->putJson("/api/admin/properties/{$property->id}", ['is_published' => true])
            ->assertOk()
            ->assertJsonPath('data.is_published', true);

        $empty = Property::factory()->published()->create();

        $this->putJson("/api/admin/properties/{$empty->id}", ['is_published' => false])
            ->assertOk()
            ->assertJsonPath('data.is_published', false);
    }

    public function test_the_feature_array_replaces_the_previous_rows(): void
    {
        $property = Property::factory()->create();
        $old = PropertyFeature::factory()->for($property)->count(3)->create();

        $this->putJson("/api/admin/properties/{$property->id}", [
            'features' => [
                ['label_en' => 'Second', 'label_ar' => 'الثاني', 'sort_order' => 2],
                ['label_en' => 'First', 'label_ar' => 'الأول', 'sort_order' => 1],
            ],
        ])->assertOk()->assertJsonPath('data.features.*.label_en', ['First', 'Second']);

        $this->assertDatabaseCount('property_features', 2);
        $old->each(fn (PropertyFeature $feature) => $this->assertModelMissing($feature));
    }

    public function test_an_update_without_features_keeps_them(): void
    {
        $property = Property::factory()->create();
        PropertyFeature::factory()->for($property)->count(2)->create();

        $this->putJson("/api/admin/properties/{$property->id}", ['price' => 990000])->assertOk();

        $this->assertDatabaseCount('property_features', 2);
        $this->assertSame('990000.00', $property->refresh()->price);
    }

    public function test_index_includes_drafts_with_cover_and_year_but_no_gallery(): void
    {
        $published = Property::factory()->published()->create(['sort_order' => 2]);
        PropertyImage::factory()->cover()->for($published)->create();
        $draft = Property::factory()->create(['sort_order' => 1]);

        $response = $this->getJson('/api/admin/properties')->assertOk();

        $response->assertJsonPath('data.*.id', [$draft->id, $published->id])
            ->assertJsonPath('data.0.is_published', false)
            ->assertJsonPath('data.1.cover_image.id', $published->coverImage->id)
            ->assertJsonPath('data.1.year_built', $published->year_built)
            ->assertJsonMissingPath('data.0.images')
            ->assertJsonMissingPath('data.0.features');
    }

    public function test_delete_removes_the_property_its_rows_and_image_files(): void
    {
        $property = Property::factory()->create();
        $images = PropertyImage::factory()->for($property)->count(2)->create();
        $images->each(fn (PropertyImage $image) => Storage::disk('public')->put($image->path, 'image-bytes'));
        PropertyFeature::factory()->for($property)->create();

        $this->deleteJson("/api/admin/properties/{$property->id}")->assertNoContent();

        $this->assertModelMissing($property);
        $this->assertDatabaseCount('property_images', 0);
        $this->assertDatabaseCount('property_features', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_unknown_property_is_a_generic_404(): void
    {
        $this->getJson('/api/admin/properties/999')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not found.']);
    }
}
