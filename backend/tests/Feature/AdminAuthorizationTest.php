<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function resources(): array
    {
        return [
            'testimonials' => ['testimonials'],
            'faqs' => ['faqs'],
            'team members' => ['team-members'],
            'clients' => ['clients'],
            'properties' => ['properties'],
        ];
    }

    #[DataProvider('resources')]
    public function test_guests_cannot_use_any_admin_crud_endpoint(string $resource): void
    {
        $this->getJson("/api/admin/{$resource}")->assertUnauthorized();
        $this->postJson("/api/admin/{$resource}", [])->assertUnauthorized();
        $this->getJson("/api/admin/{$resource}/1")->assertUnauthorized();
        $this->putJson("/api/admin/{$resource}/1", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/{$resource}/1")->assertUnauthorized();
    }

    public function test_guests_cannot_use_the_property_image_endpoints(): void
    {
        $image = PropertyImage::factory()->create();
        $base = "/api/admin/properties/{$image->property_id}/images";

        $this->postJson($base, [])->assertUnauthorized();
        $this->patchJson("{$base}/{$image->id}", ['is_cover' => true])->assertUnauthorized();
        $this->deleteJson("{$base}/{$image->id}")->assertUnauthorized();

        $this->assertModelExists($image);
    }

    public function test_guests_cannot_change_content(): void
    {
        $faq = Faq::factory()->create();
        $property = Property::factory()->create();

        $this->putJson("/api/admin/faqs/{$faq->id}", ['is_published' => true])->assertUnauthorized();
        $this->deleteJson("/api/admin/properties/{$property->id}")->assertUnauthorized();

        $this->assertFalse($faq->refresh()->is_published);
        $this->assertModelExists($property);
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $this->withToken('1|not-a-real-token')->getJson('/api/admin/faqs')->assertUnauthorized();
    }

    public function test_every_admin_route_requires_sanctum_authentication(): void
    {
        $adminRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => str_starts_with($route->uri(), 'api/admin'));

        $this->assertNotEmpty($adminRoutes);

        $adminRoutes->each(function (RoutingRoute $route) {
            $this->assertContains(
                'auth:sanctum',
                $route->gatherMiddleware(),
                "Route [{$route->uri()}] is missing auth:sanctum.",
            );
        });
    }
}
