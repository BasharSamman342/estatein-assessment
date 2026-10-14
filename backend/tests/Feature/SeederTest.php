<?php

namespace Tests\Feature;

use App\Enums\PriceRange;
use App\Enums\PropertyType;
use App\Enums\SizeRange;
use App\Enums\YearRange;
use App\Models\Client;
use App\Models\Faq;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The sample data must be enough to show every section and exercise every search option (design doc, "Seed data").
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed();
    }

    public function test_each_cms_section_has_six_published_rows_and_one_draft(): void
    {
        /** @var class-string<Model> $model */
        foreach ([Testimonial::class, Faq::class, TeamMember::class, Client::class] as $model) {
            $this->assertSame(6, $model::query()->where('is_published', true)->count(), $model);
            $this->assertSame(1, $model::query()->where('is_published', false)->count(), $model);
        }

        $this->assertSame(9, Property::query()->where('is_published', true)->count());
        $this->assertSame(1, Property::query()->where('is_published', false)->count());
    }

    public function test_every_property_type_and_search_band_has_a_published_match(): void
    {
        $filters = [
            ...array_map(fn ($case) => "type={$case->value}", PropertyType::cases()),
            ...array_map(fn ($case) => "price_range={$case->value}", PriceRange::cases()),
            ...array_map(fn ($case) => "size_range={$case->value}", SizeRange::cases()),
            ...array_map(fn ($case) => "year_range={$case->value}", YearRange::cases()),
        ];

        foreach ($filters as $filter) {
            $this->assertGreaterThan(0, $this->getJson("/api/properties?{$filter}")->assertOk()->json('meta.total'), $filter);
        }

        $this->assertGreaterThanOrEqual(2, count($this->getJson('/api/property-filters')->json('data.locations')));
    }

    public function test_each_published_property_has_three_images_with_the_first_as_cover_and_four_features(): void
    {
        Property::query()->where('is_published', true)->with(['images', 'features'])->get()->each(function (Property $property) {
            $this->assertCount(3, $property->images, $property->slug);
            $this->assertTrue($property->images->first()->is_cover, $property->slug);
            $this->assertSame(1, $property->images->where('is_cover', true)->count(), $property->slug);
            $this->assertCount(4, $property->features, $property->slug);
        });
    }

    public function test_a_published_arabic_location_mixes_latin_digits_and_a_price(): void
    {
        $mixed = Property::query()
            ->where('is_published', true)
            ->get()
            ->first(fn (Property $property) => preg_match('/\p{Arabic}/u', $property->location_ar)
                && preg_match('/[0-9]/', $property->location_ar)
                && str_contains($property->location_ar, '$'));

        $this->assertNotNull($mixed);
    }

    public function test_seeded_images_exist_on_the_public_disk(): void
    {
        $paths = [
            ...PropertyImage::query()->pluck('path'),
            ...Testimonial::query()->pluck('avatar_path'),
            ...TeamMember::query()->pluck('photo_path'),
            ...Client::query()->pluck('logo_path'),
        ];

        // 9 published properties × 3 images + 1 on the draft, plus 7 avatars, 7 portraits and 7 logos.
        $this->assertCount(49, $paths);
        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_the_admin_can_log_in_with_the_seeded_credentials(): void
    {
        $this->postJson('/api/login', [
            'email' => config('estatein.admin.email'),
            'password' => config('estatein.admin.password'),
        ])->assertOk()->assertJsonPath('user.name', 'Estatein Admin');
    }

    public function test_public_endpoints_return_both_languages(): void
    {
        $this->getJson('/api/testimonials')->assertOk()->assertJsonCount(6, 'data')->assertJsonPath('data.0.author_name_ar', 'وايد وارن');
        $this->getJson('/api/faqs')->assertOk()->assertJsonCount(6, 'data');
        $this->getJson('/api/team-members')->assertOk()->assertJsonCount(6, 'data')->assertJsonPath('data.0.role_ar', 'المؤسس');
        $this->getJson('/api/clients')->assertOk()->assertJsonCount(6, 'data');
        $this->getJson('/api/properties?per_page=3')->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('meta.total', 9);
        $this->getJson('/api/properties/seaside-serenity-villa')->assertOk()->assertJsonCount(3, 'data.images');
        $this->getJson('/api/properties/lakeside-haven')->assertNotFound();
    }

    public function test_seeding_again_does_not_duplicate_content(): void
    {
        $this->seed();

        $this->assertSame(10, Property::query()->count());
        $this->assertSame(7, Faq::query()->count());
    }
}
