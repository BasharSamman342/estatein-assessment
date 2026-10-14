<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PropertyFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_q_matches_the_title_of_the_requested_locale_only(): void
    {
        $villa = Property::factory()->published()->create(['title_en' => 'Seaside Serenity Villa', 'title_ar' => 'فيلا البحر']);
        $flat = Property::factory()->published()->create(['title_en' => 'Metropolitan Haven', 'title_ar' => 'ملاذ Seaside']);

        $this->getJson('/api/properties?locale=en&q=seaside')->assertOk()->assertJsonPath('data.*.id', [$villa->id]);
        $this->getJson('/api/properties?locale=ar&q=seaside')->assertOk()->assertJsonPath('data.*.id', [$flat->id]);
        $this->getJson('/api/properties?locale=ar&q='.urlencode('فيلا'))->assertOk()->assertJsonPath('data.*.id', [$villa->id]);
    }

    public function test_q_treats_like_wildcards_literally(): void
    {
        Property::factory()->published()->create(['title_en' => 'Plain Title']);
        $percent = Property::factory()->published()->create(['title_en' => '100% Ocean View']);

        $this->getJson('/api/properties?q='.urlencode('%'))->assertOk()->assertJsonPath('data.*.id', [$percent->id]);
        $this->getJson('/api/properties?q=_')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_location_is_an_exact_case_sensitive_match_in_the_requested_locale(): void
    {
        $dubai = Property::factory()->published()->create(['location_en' => 'Dubai', 'location_ar' => 'دبي']);
        Property::factory()->published()->create(['location_en' => 'dubai', 'location_ar' => 'دبي الجديدة']);
        Property::factory()->published()->create(['location_en' => 'Dubai Marina', 'location_ar' => 'مرسى دبي']);

        $this->getJson('/api/properties?location=Dubai')->assertOk()->assertJsonPath('data.*.id', [$dubai->id]);
        $this->getJson('/api/properties?locale=ar&location='.urlencode('دبي'))->assertOk()->assertJsonPath('data.*.id', [$dubai->id]);
    }

    public function test_type_filter(): void
    {
        $villa = Property::factory()->published()->create(['property_type' => 'villa']);
        Property::factory()->published()->create(['property_type' => 'apartment']);

        $this->getJson('/api/properties?type=villa')->assertOk()->assertJsonPath('data.*.id', [$villa->id]);
    }

    /**
     * @return array<string, array{string, string, array<int, int>, array<int, int>}>
     */
    public static function bands(): array
    {
        return [
            'price under 500k' => ['price_range', 'lt500000', [499999], [500000]],
            'price 500k to 1m (inclusive)' => ['price_range', '500000to1000000', [500000, 1000000], [499999, 1000001]],
            'price over 1m' => ['price_range', 'gt1000000', [1000001], [1000000]],
            'size under 1500' => ['size_range', 'lt1500', [1499], [1500]],
            'size 1500 to 3000 (inclusive)' => ['size_range', '1500to3000', [1500, 3000], [1499, 3001]],
            'size over 3000' => ['size_range', 'gt3000', [3001], [3000]],
            'year before 2000' => ['year_range', 'pre2000', [1999], [2000]],
            'year 2000 to 2015 (inclusive)' => ['year_range', '2000to2015', [2000, 2015], [1999, 2016]],
            'year after 2015' => ['year_range', 'post2015', [2016], [2015]],
        ];
    }

    /**
     * @param  array<int, int>  $matching
     * @param  array<int, int>  $notMatching
     */
    #[DataProvider('bands')]
    public function test_band_filters_include_their_edges(string $parameter, string $band, array $matching, array $notMatching): void
    {
        $column = ['price_range' => 'price', 'size_range' => 'area_sqft', 'year_range' => 'year_built'][$parameter];

        $expected = array_map(fn (int $value) => Property::factory()->published()->create([$column => $value])->id, $matching);
        array_map(fn (int $value) => Property::factory()->published()->create([$column => $value]), $notMatching);

        $this->getJson("/api/properties?{$parameter}={$band}")
            ->assertOk()
            ->assertJsonPath('data.*.id', $expected);
    }

    public function test_filters_combine_with_and(): void
    {
        $match = Property::factory()->published()->create(['property_type' => 'villa', 'price' => 600000]);
        Property::factory()->published()->create(['property_type' => 'villa', 'price' => 200000]);
        Property::factory()->published()->create(['property_type' => 'apartment', 'price' => 600000]);

        $this->getJson('/api/properties?type=villa&price_range=500000to1000000')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$match->id]);
    }

    #[DataProvider('invalidQueries')]
    public function test_unknown_filter_values_are_rejected(string $query, string $field): void
    {
        $this->getJson("/api/properties?{$query}")->assertUnprocessable()->assertJsonValidationErrors([$field]);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidQueries(): array
    {
        return [
            'price band' => ['price_range=cheap', 'price_range'],
            'size band' => ['size_range=huge', 'size_range'],
            'year band' => ['year_range=1990s', 'year_range'],
            'type' => ['type=castle', 'type'],
            'locale' => ['locale=fr', 'locale'],
            'per page too large' => ['per_page=51', 'per_page'],
            'q too long' => ['q='.str_repeat('a', 101), 'q'],
        ];
    }

    public function test_drafts_are_excluded_and_results_follow_sort_order(): void
    {
        $second = Property::factory()->published()->create(['sort_order' => 2]);
        Property::factory()->create(['sort_order' => 0]);
        $first = Property::factory()->published()->create(['sort_order' => 1]);

        $this->getJson('/api/properties')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$first->id, $second->id])
            ->assertJsonMissingPath('data.0.is_published')
            ->assertJsonMissingPath('data.0.images')
            ->assertJsonMissingPath('data.0.features');
    }

    public function test_results_are_paginated_nine_per_page_by_default(): void
    {
        Property::factory()->published()->count(11)->create();

        $this->getJson('/api/properties')
            ->assertOk()
            ->assertJsonCount(9, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 9)
            ->assertJsonPath('meta.total', 11);

        $this->getJson('/api/properties?per_page=3&page=4&type=')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 4);
    }

    public function test_pagination_links_keep_the_filters(): void
    {
        Property::factory()->published()->count(4)->create(['property_type' => 'villa']);

        $next = $this->getJson('/api/properties?type=villa&per_page=2')->assertOk()->json('links.next');

        $this->assertStringContainsString('type=villa', $next);
        $this->assertStringContainsString('page=2', $next);
    }

    public function test_list_item_shape_includes_the_cover_image(): void
    {
        $property = Property::factory()->published()->create();
        $cover = PropertyImage::factory()->cover()->for($property)->create(['alt_en' => 'Front view', 'alt_ar' => 'الواجهة']);
        PropertyImage::factory()->for($property)->create(['sort_order' => 1]);

        $this->getJson('/api/properties')
            ->assertOk()
            ->assertJsonPath('data.0.cover_image.id', $cover->id)
            ->assertJsonPath('data.0.cover_image.alt_ar', 'الواجهة')
            ->assertJsonStructure(['data' => [[
                'id', 'slug', 'title_en', 'title_ar', 'description_en', 'description_ar', 'price', 'currency',
                'location_en', 'location_ar', 'bedrooms', 'bathrooms', 'area_sqft', 'year_built', 'property_type',
                'sort_order', 'cover_image' => ['id', 'url', 'alt_en', 'alt_ar'],
            ]]]);
    }

    public function test_the_list_runs_a_constant_number_of_queries(): void
    {
        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/properties')->assertOk();

            return count(DB::getQueryLog());
        };

        Property::factory()->published()->count(2)->has(PropertyImage::factory()->cover(), 'images')->create();
        $few = $countQueries();

        Property::factory()->published()->count(7)->has(PropertyImage::factory()->cover(), 'images')->create();
        $many = $countQueries();

        $this->assertSame($few, $many);
    }

    public function test_show_returns_a_published_property_with_gallery_and_features(): void
    {
        $property = Property::factory()->published()->create(['slug' => 'seaside-serenity-villa']);
        $second = PropertyImage::factory()->for($property)->create(['sort_order' => 2]);
        $cover = PropertyImage::factory()->cover()->for($property)->create(['sort_order' => 1]);

        $this->getJson('/api/properties/seaside-serenity-villa')
            ->assertOk()
            ->assertJsonPath('data.id', $property->id)
            ->assertJsonPath('data.title_ar', $property->title_ar)
            ->assertJsonPath('data.cover_image.id', $cover->id)
            ->assertJsonPath('data.images.*.id', [$cover->id, $second->id])
            ->assertJsonPath('data.images.0.is_cover', true)
            ->assertJsonPath('data.features', [])
            ->assertJsonMissingPath('data.is_published');
    }

    public function test_show_hides_unpublished_and_unknown_slugs(): void
    {
        Property::factory()->create(['slug' => 'secret-draft']);

        $this->getJson('/api/properties/secret-draft')->assertNotFound()->assertExactJson(['message' => 'Not found.']);
        $this->getJson('/api/properties/nope')->assertNotFound()->assertExactJson(['message' => 'Not found.']);
    }

    public function test_property_filters_lists_distinct_published_locations_sorted(): void
    {
        Property::factory()->published()->create(['location_en' => 'Malibu', 'location_ar' => 'ماليبو']);
        Property::factory()->published()->create(['location_en' => 'Austin', 'location_ar' => 'أوستن']);
        Property::factory()->published()->create(['location_en' => 'Malibu', 'location_ar' => 'ماليبو']);
        Property::factory()->create(['location_en' => 'Hidden Draft', 'location_ar' => 'مسودة']);

        $this->getJson('/api/property-filters')
            ->assertOk()
            ->assertExactJson(['data' => ['locations' => ['Austin', 'Malibu']]]);

        $this->getJson('/api/property-filters?locale=ar')
            ->assertOk()
            ->assertExactJson(['data' => ['locations' => ['أوستن', 'ماليبو']]]);

        $this->getJson('/api/property-filters?locale=fr')->assertUnprocessable();
    }
}
