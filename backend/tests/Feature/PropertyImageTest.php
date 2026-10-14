<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyImageTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAsAdmin();
        $this->property = Property::factory()->create();
    }

    public function test_a_real_png_is_stored_and_becomes_the_cover(): void
    {
        $response = $this->post($this->imagesUrl(), [
            'image' => UploadedFile::fake()->image('front.png', 800, 600),
            'alt_en' => 'Front view',
            'alt_ar' => 'الواجهة الأمامية',
        ])->assertCreated();

        $image = PropertyImage::query()->sole();
        $this->assertStringStartsWith("properties/{$this->property->id}/", $image->path);
        $this->assertStringEndsWith('.png', $image->path);
        Storage::disk('public')->assertExists($image->path);

        $response->assertExactJson(['data' => [
            'id' => $image->id,
            'url' => Storage::disk('public')->url($image->path),
            'alt_en' => 'Front view',
            'alt_ar' => 'الواجهة الأمامية',
            'is_cover' => true,
            'sort_order' => 0,
        ]]);
    }

    public function test_later_uploads_are_appended_and_are_not_the_cover(): void
    {
        $this->post($this->imagesUrl(), ['image' => UploadedFile::fake()->image('a.jpg')])->assertCreated();
        $this->post($this->imagesUrl(), ['image' => UploadedFile::fake()->image('b.webp')])
            ->assertCreated()
            ->assertJsonPath('data.is_cover', false)
            ->assertJsonPath('data.sort_order', 1);

        $this->post($this->imagesUrl(), ['image' => UploadedFile::fake()->image('c.jpg'), 'sort_order' => 7])
            ->assertCreated()
            ->assertJsonPath('data.sort_order', 7);
    }

    public function test_a_text_file_renamed_to_jpg_is_rejected(): void
    {
        $this->post($this->imagesUrl(), [
            'image' => $this->uploadWithContent('house.jpg', "just some text\n", 'image/jpeg'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['image']);

        $this->assertDatabaseCount('property_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_unsupported_or_oversized_files_are_rejected(): void
    {
        $this->post($this->imagesUrl(), ['image' => UploadedFile::fake()->image('anim.gif')])
            ->assertUnprocessable()->assertJsonValidationErrors(['image']);

        $this->post($this->imagesUrl(), ['image' => UploadedFile::fake()->image('huge.jpg')->size(5121)])
            ->assertUnprocessable()->assertJsonValidationErrors(['image']);

        $this->post($this->imagesUrl(), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['image']);
    }

    public function test_a_property_holds_at_most_twenty_images(): void
    {
        PropertyImage::factory()->for($this->property)->count(20)->create();

        $this->post($this->imagesUrl(), ['image' => UploadedFile::fake()->image('one-more.jpg')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_setting_a_new_cover_clears_the_previous_one(): void
    {
        $first = $this->upload();
        $second = $this->upload();

        $this->patchJson($this->imageUrl($second), ['is_cover' => true])
            ->assertOk()
            ->assertJsonPath('data.is_cover', true);

        $this->assertFalse($first->refresh()->is_cover);
        $this->assertSame([$second->id], $this->property->images()->where('is_cover', true)->pluck('id')->all());
    }

    public function test_the_cover_flag_cannot_simply_be_removed(): void
    {
        $cover = $this->upload();

        $this->patchJson($this->imageUrl($cover), ['is_cover' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_cover']);

        $this->assertTrue($cover->refresh()->is_cover);
    }

    public function test_alt_text_and_position_can_be_updated(): void
    {
        $image = $this->upload();

        $this->patchJson($this->imageUrl($image), ['alt_en' => 'Pool', 'alt_ar' => 'المسبح', 'sort_order' => 5])
            ->assertOk()
            ->assertJsonPath('data.alt_en', 'Pool')
            ->assertJsonPath('data.alt_ar', 'المسبح')
            ->assertJsonPath('data.sort_order', 5)
            ->assertJsonPath('data.is_cover', true);
    }

    public function test_delete_removes_the_row_and_the_file(): void
    {
        $this->upload();
        $image = $this->upload();

        $this->deleteJson($this->imageUrl($image))->assertNoContent();

        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_deleting_the_cover_promotes_the_next_image(): void
    {
        $cover = $this->upload();
        $third = $this->upload(['sort_order' => 3]);
        $second = $this->upload(['sort_order' => 2]);

        $this->deleteJson($this->imageUrl($cover))->assertNoContent();

        $this->assertTrue($second->refresh()->is_cover);
        $this->assertFalse($third->refresh()->is_cover);
    }

    public function test_the_last_image_of_a_published_property_cannot_be_deleted(): void
    {
        $image = $this->upload();
        $this->property->update(['is_published' => true]);

        $this->deleteJson($this->imageUrl($image))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        $this->assertModelExists($image);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_the_last_image_of_a_draft_can_be_deleted(): void
    {
        $image = $this->upload();

        $this->deleteJson($this->imageUrl($image))->assertNoContent();

        $this->assertDatabaseCount('property_images', 0);
    }

    public function test_an_image_of_another_property_is_not_found(): void
    {
        $other = PropertyImage::factory()->for(Property::factory())->create();

        $this->patchJson($this->imageUrl($other), ['alt_en' => 'x'])->assertNotFound();
        $this->deleteJson($this->imageUrl($other))->assertNotFound();
        $this->assertModelExists($other);
    }

    public function test_several_stored_covers_are_reduced_to_the_first_on_the_next_write(): void
    {
        $later = PropertyImage::factory()->cover()->for($this->property)->create(['sort_order' => 2]);
        $earlier = PropertyImage::factory()->cover()->for($this->property)->create(['sort_order' => 1]);

        // Before any write, readers already treat the lowest sort_order as the cover.
        $this->assertSame($earlier->id, $this->property->coverImage->id);

        $this->patchJson($this->imageUrl($later), ['alt_en' => 'Garden'])->assertOk();

        $this->assertTrue($earlier->refresh()->is_cover);
        $this->assertFalse($later->refresh()->is_cover);
    }

    private function imagesUrl(): string
    {
        return "/api/admin/properties/{$this->property->id}/images";
    }

    private function imageUrl(PropertyImage $image): string
    {
        return "{$this->imagesUrl()}/{$image->id}";
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upload(array $data = []): PropertyImage
    {
        $id = $this->post($this->imagesUrl(), ['image' => UploadedFile::fake()->image('photo.jpg'), ...$data])
            ->assertCreated()
            ->json('data.id');

        return PropertyImage::query()->findOrFail($id);
    }
}
