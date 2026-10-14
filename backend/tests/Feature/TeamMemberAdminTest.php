<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamMemberAdminTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = [
        'name_en' => 'Max Mitchell',
        'name_ar' => 'ماكس ميتشل',
        'role_en' => 'Founder',
        'role_ar' => 'المؤسس',
        'bio_en' => 'Started Estatein in 2010.',
        'bio_ar' => 'أسس إستيتين عام 2010.',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAsAdmin();
    }

    public function test_create_with_a_photo(): void
    {
        $this->post('/api/admin/team-members', [...self::VALID, 'photo' => UploadedFile::fake()->image('max.jpg', 400, 400)])
            ->assertCreated()
            ->assertJson(['data' => [...self::VALID, 'is_published' => false]]);

        $member = TeamMember::query()->sole();
        Storage::disk('public')->assertExists($member->photo_path);
        $this->assertStringStartsWith('team/', $member->photo_path);
    }

    public function test_create_without_name_en_is_rejected(): void
    {
        $this->postJson('/api/admin/team-members', [...self::VALID, 'name_en' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name_en']);
    }

    public function test_role_is_required_and_bio_is_optional(): void
    {
        $this->postJson('/api/admin/team-members', [...self::VALID, 'role_ar' => '', 'bio_en' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role_ar'])
            ->assertJsonMissingValidationErrors(['bio_en']);
    }

    public function test_update_with_a_new_photo_deletes_the_previous_file(): void
    {
        $this->post('/api/admin/team-members', [...self::VALID, 'photo' => UploadedFile::fake()->image('old.jpg')])->assertCreated();
        $member = TeamMember::query()->sole();
        $oldPath = $member->photo_path;

        $this->post("/api/admin/team-members/{$member->id}", [
            '_method' => 'PUT',
            ...self::VALID,
            'role_en' => 'Chief Executive',
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ])->assertOk()->assertJsonPath('data.role_en', 'Chief Executive');

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($member->refresh()->photo_path);
    }

    public function test_delete_removes_the_row_and_the_photo(): void
    {
        $this->post('/api/admin/team-members', [...self::VALID, 'photo' => UploadedFile::fake()->image('max.jpg')])->assertCreated();
        $member = TeamMember::query()->sole();

        $this->deleteJson("/api/admin/team-members/{$member->id}")->assertNoContent();

        $this->assertModelMissing($member);
        Storage::disk('public')->assertMissing($member->photo_path);
    }
}
