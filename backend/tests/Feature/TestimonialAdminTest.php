<?php

namespace Tests\Feature;

use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestimonialAdminTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = [
        'quote_en' => 'Estatein made finding my dream home easy.',
        'quote_ar' => 'جعلت إستيتين العثور على منزل أحلامي أمراً سهلاً.',
        'author_name_en' => 'Wade Warren',
        'author_name_ar' => 'وايد وارن',
        'role_en' => 'USA, California',
        'role_ar' => 'الولايات المتحدة، كاليفورنيا',
        'rating' => 5,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAsAdmin();
    }

    public function test_create_with_an_avatar_stores_the_file_and_returns_its_url(): void
    {
        $response = $this->post('/api/admin/testimonials', [
            ...self::VALID,
            'avatar' => UploadedFile::fake()->image('wade.png', 120, 120),
        ])->assertCreated();

        $testimonial = Testimonial::query()->sole();
        Storage::disk('public')->assertExists($testimonial->avatar_path);
        $this->assertStringStartsWith('testimonials/', $testimonial->avatar_path);
        $this->assertStringEndsWith('.png', $testimonial->avatar_path);

        $response->assertJson(['data' => [...self::VALID, 'is_published' => false]])
            ->assertJsonPath('data.avatar_url', Storage::disk('public')->url($testimonial->avatar_path));
    }

    public function test_avatar_and_rating_are_optional(): void
    {
        $this->postJson('/api/admin/testimonials', [...self::VALID, 'rating' => null, 'role_en' => null, 'role_ar' => null])
            ->assertCreated()
            ->assertJsonPath('data.rating', null)
            ->assertJsonPath('data.avatar_url', null);
    }

    public function test_create_without_quote_en_is_rejected(): void
    {
        $data = self::VALID;
        unset($data['quote_en']);

        $this->postJson('/api/admin/testimonials', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quote_en']);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $this->postJson('/api/admin/testimonials', [...self::VALID, 'rating' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        $this->postJson('/api/admin/testimonials', [...self::VALID, 'rating' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_a_text_file_renamed_to_jpg_is_rejected(): void
    {
        $this->post('/api/admin/testimonials', [
            ...self::VALID,
            'avatar' => $this->uploadWithContent('avatar.jpg', 'definitely not an image', 'image/jpeg'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['avatar']);

        $this->assertDatabaseCount('testimonials', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_avatar_over_five_megabytes_is_rejected(): void
    {
        $this->post('/api/admin/testimonials', [
            ...self::VALID,
            'avatar' => UploadedFile::fake()->image('big.jpg')->size(5121),
        ])->assertUnprocessable()->assertJsonValidationErrors(['avatar']);
    }

    public function test_update_with_a_new_avatar_deletes_the_previous_file(): void
    {
        $this->post('/api/admin/testimonials', [...self::VALID, 'avatar' => UploadedFile::fake()->image('old.png')])->assertCreated();
        $testimonial = Testimonial::query()->sole();
        $oldPath = $testimonial->avatar_path;

        // Multipart updates go through POST with _method=PUT, since PHP does not parse multipart PUT bodies.
        $this->post("/api/admin/testimonials/{$testimonial->id}", [
            '_method' => 'PUT',
            'avatar' => UploadedFile::fake()->image('new.webp'),
        ])->assertOk();

        $newPath = $testimonial->refresh()->avatar_path;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
        $this->assertSame(self::VALID['quote_en'], $testimonial->quote_en);
    }

    public function test_update_without_a_file_keeps_the_avatar(): void
    {
        $this->post('/api/admin/testimonials', [...self::VALID, 'avatar' => UploadedFile::fake()->image('a.png')])->assertCreated();
        $testimonial = Testimonial::query()->sole();

        $this->putJson("/api/admin/testimonials/{$testimonial->id}", ['quote_en' => 'Updated quote.'])->assertOk();

        $this->assertSame($testimonial->avatar_path, $testimonial->refresh()->avatar_path);
        Storage::disk('public')->assertExists($testimonial->avatar_path);
    }

    public function test_failed_validation_on_update_leaves_the_old_avatar_alone(): void
    {
        $this->post('/api/admin/testimonials', [...self::VALID, 'avatar' => UploadedFile::fake()->image('a.png')])->assertCreated();
        $testimonial = Testimonial::query()->sole();

        $this->post("/api/admin/testimonials/{$testimonial->id}", [
            '_method' => 'PUT',
            'rating' => 9,
            'avatar' => UploadedFile::fake()->image('b.png'),
        ])->assertUnprocessable();

        $this->assertSame([$testimonial->avatar_path], Storage::disk('public')->allFiles());
    }

    public function test_update_changes_the_fields(): void
    {
        $testimonial = Testimonial::factory()->create();

        $this->putJson("/api/admin/testimonials/{$testimonial->id}", [...self::VALID, 'is_published' => true])
            ->assertOk()
            ->assertJson(['data' => [...self::VALID, 'id' => $testimonial->id, 'is_published' => true]]);
    }

    public function test_delete_removes_the_row_and_the_avatar_file(): void
    {
        $this->post('/api/admin/testimonials', [...self::VALID, 'avatar' => UploadedFile::fake()->image('a.png')])->assertCreated();
        $testimonial = Testimonial::query()->sole();

        $this->deleteJson("/api/admin/testimonials/{$testimonial->id}")->assertNoContent();

        $this->assertModelMissing($testimonial);
        Storage::disk('public')->assertMissing($testimonial->avatar_path);
    }
}
