<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryTest extends TestCase
{
    use RefreshDatabase;

    private const CONTACT = [
        'type' => 'contact',
        'name' => 'Layla Hassan',
        'email' => 'layla@example.com',
        'phone' => '+971 50 123 4567',
        'message' => 'أرغب في معرفة المزيد عن خدماتكم. Thanks!',
    ];

    public function test_a_valid_contact_inquiry_is_stored(): void
    {
        $response = $this->postJson('/api/inquiries', self::CONTACT)->assertCreated();

        $inquiry = Inquiry::query()->sole();
        $response->assertExactJson(['data' => ['id' => $inquiry->id]]);
        $this->assertDatabaseHas('inquiries', [...self::CONTACT, 'property_id' => null]);
    }

    public function test_phone_is_optional(): void
    {
        $this->postJson('/api/inquiries', [...self::CONTACT, 'phone' => null])->assertCreated();
    }

    public function test_missing_email_is_rejected(): void
    {
        $data = self::CONTACT;
        unset($data['email']);

        $this->postJson('/api/inquiries', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/inquiries', [...self::CONTACT, 'email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_a_property_inquiry_needs_a_published_property(): void
    {
        $published = Property::factory()->published()->create();
        $draft = Property::factory()->create();

        $this->postJson('/api/inquiries', [...self::CONTACT, 'type' => 'property', 'property_id' => $published->id])
            ->assertCreated();

        $this->postJson('/api/inquiries', [...self::CONTACT, 'type' => 'property', 'property_id' => $draft->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);

        $this->postJson('/api/inquiries', [...self::CONTACT, 'type' => 'property', 'property_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);

        $this->postJson('/api/inquiries', [...self::CONTACT, 'type' => 'property'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);

        $this->assertDatabaseCount('inquiries', 1);
    }

    public function test_a_contact_inquiry_cannot_reference_a_property(): void
    {
        $property = Property::factory()->published()->create();

        $this->postJson('/api/inquiries', [...self::CONTACT, 'property_id' => $property->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_id']);
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->postJson('/api/inquiries', [...self::CONTACT, 'type' => 'newsletter'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_submissions_are_throttled(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/inquiries', self::CONTACT)->assertCreated();
        }

        $this->postJson('/api/inquiries', self::CONTACT)->assertTooManyRequests();
    }
}
