<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientAdminTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = [
        'name_en' => 'ABC Corporation',
        'name_ar' => 'مؤسسة ABC',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAsAdmin();
    }

    public function test_create_stores_the_logo(): void
    {
        $this->post('/api/admin/clients', [...self::VALID, 'logo' => UploadedFile::fake()->image('abc.png', 300, 120)])
            ->assertCreated()
            ->assertJson(['data' => self::VALID]);

        $client = Client::query()->sole();
        Storage::disk('public')->assertExists($client->logo_path);
        $this->assertStringStartsWith('clients/', $client->logo_path);
    }

    public function test_create_without_a_logo_is_rejected(): void
    {
        $this->postJson('/api/admin/clients', self::VALID)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_svg_logos_are_rejected(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->post('/api/admin/clients', [...self::VALID, 'logo' => $this->uploadWithContent('logo.png', $svg, 'image/png')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logo']);
    }

    public function test_update_without_a_logo_keeps_the_current_one(): void
    {
        $this->post('/api/admin/clients', [...self::VALID, 'logo' => UploadedFile::fake()->image('abc.png')])->assertCreated();
        $client = Client::query()->sole();

        $this->putJson("/api/admin/clients/{$client->id}", ['name_en' => 'ABC Corp', 'name_ar' => 'شركة ABC'])
            ->assertOk()
            ->assertJsonPath('data.name_en', 'ABC Corp');

        $this->assertSame($client->logo_path, $client->refresh()->logo_path);
    }

    public function test_update_with_a_new_logo_deletes_the_previous_file(): void
    {
        $this->post('/api/admin/clients', [...self::VALID, 'logo' => UploadedFile::fake()->image('old.png')])->assertCreated();
        $client = Client::query()->sole();
        $oldPath = $client->logo_path;

        $this->post("/api/admin/clients/{$client->id}", ['_method' => 'PUT', 'logo' => UploadedFile::fake()->image('new.png')])
            ->assertOk();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($client->refresh()->logo_path);
    }

    public function test_delete_removes_the_row_and_the_logo(): void
    {
        $this->post('/api/admin/clients', [...self::VALID, 'logo' => UploadedFile::fake()->image('abc.png')])->assertCreated();
        $client = Client::query()->sole();

        $this->deleteJson("/api/admin/clients/{$client->id}")->assertNoContent();

        $this->assertModelMissing($client);
        Storage::disk('public')->assertMissing($client->logo_path);
    }
}
