<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Faq;
use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyImage;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, class-string<Model>, array<int, string>}>
     */
    public static function cmsSections(): array
    {
        return [
            'testimonials' => ['/api/testimonials', Testimonial::class, ['quote_en', 'quote_ar', 'author_name_en', 'author_name_ar', 'rating', 'avatar_url']],
            'faqs' => ['/api/faqs', Faq::class, ['question_en', 'question_ar', 'answer_en', 'answer_ar']],
            'team members' => ['/api/team-members', TeamMember::class, ['name_en', 'name_ar', 'role_en', 'role_ar', 'photo_url']],
            'clients' => ['/api/clients', Client::class, ['name_en', 'name_ar', 'logo_url']],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, string>  $fields
     */
    #[DataProvider('cmsSections')]
    public function test_lists_only_published_rows_in_sort_order(string $uri, string $model, array $fields): void
    {
        $later = $model::factory()->published()->create(['sort_order' => 2]);
        $model::factory()->create(['sort_order' => 0]);
        $first = $model::factory()->published()->create(['sort_order' => 1]);

        $this->getJson($uri)
            ->assertOk()
            ->assertJsonPath('data.*.id', [$first->id, $later->id])
            ->assertJsonStructure(['data' => [['id', ...$fields, 'sort_order']]])
            ->assertJsonMissingPath('data.0.is_published');
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<int, string>  $fields
     */
    #[DataProvider('cmsSections')]
    public function test_public_payloads_never_expose_file_paths(string $uri, string $model, array $fields): void
    {
        $model::factory()->published()->create();

        $body = $this->getJson($uri)->assertOk()->json('data.0');

        $this->assertEmpty(array_filter(array_keys($body), fn (string $key) => str_ends_with($key, '_path')));
    }

    public function test_property_payloads_expose_no_paths_or_publish_status_at_any_depth(): void
    {
        $property = Property::factory()->published()->create(['slug' => 'open-house']);
        PropertyImage::factory()->cover()->for($property)->create();
        PropertyFeature::factory()->for($property)->create();

        foreach (['/api/properties', '/api/properties/open-house'] as $uri) {
            // Only the data: pagination meta has its own "path" key (the endpoint URL).
            $keys = $this->keysOf($this->getJson($uri)->assertOk()->json('data'));

            $this->assertNotContains('path', $keys, $uri);
            $this->assertNotContains('is_published', $keys, $uri);
            $this->assertEmpty(array_filter($keys, fn (string $key) => str_ends_with($key, '_path')), $uri);
        }
    }

    /**
     * Every array key in a decoded JSON payload, recursively.
     *
     * @param  array<mixed>  $payload
     * @return array<int, string>
     */
    private function keysOf(array $payload): array
    {
        $keys = [];

        foreach ($payload as $key => $value) {
            if (is_string($key)) {
                $keys[] = $key;
            }

            if (is_array($value)) {
                $keys = [...$keys, ...$this->keysOf($value)];
            }
        }

        return $keys;
    }
}
