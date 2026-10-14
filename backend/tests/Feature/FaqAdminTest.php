<?php

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqAdminTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = [
        'question_en' => 'How do I search for properties on Estatein?',
        'question_ar' => 'كيف أبحث عن العقارات في إستيتين؟',
        'answer_en' => 'Use the search bar on the properties page.',
        'answer_ar' => 'استخدم شريط البحث في صفحة العقارات.',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
    }

    public function test_create_stores_both_languages_as_a_draft(): void
    {
        $response = $this->postJson('/api/admin/faqs', self::VALID)->assertCreated();

        $response->assertJson(['data' => [...self::VALID, 'sort_order' => 0, 'is_published' => false]]);
        $this->assertDatabaseHas('faqs', [...self::VALID, 'is_published' => false]);
    }

    public function test_create_without_question_en_is_rejected(): void
    {
        $this->postJson('/api/admin/faqs', [...self::VALID, 'question_en' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question_en'])
            ->assertJsonMissingValidationErrors(['question_ar', 'answer_en', 'answer_ar']);

        $this->assertDatabaseCount('faqs', 0);
    }

    public function test_invalid_sort_order_and_publish_flag_are_rejected(): void
    {
        $this->postJson('/api/admin/faqs', [...self::VALID, 'sort_order' => -1, 'is_published' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort_order', 'is_published']);
    }

    public function test_multipart_style_boolean_strings_are_accepted(): void
    {
        $this->post('/api/admin/faqs', [...self::VALID, 'is_published' => 'true', 'sort_order' => '3'])
            ->assertCreated()
            ->assertJsonPath('data.is_published', true)
            ->assertJsonPath('data.sort_order', 3);
    }

    public function test_update_replaces_the_fields(): void
    {
        $faq = Faq::factory()->create();

        $this->putJson("/api/admin/faqs/{$faq->id}", [...self::VALID, 'sort_order' => 4, 'is_published' => true])
            ->assertOk()
            ->assertJson(['data' => [...self::VALID, 'id' => $faq->id, 'sort_order' => 4, 'is_published' => true]]);
    }

    public function test_partial_update_only_touches_the_sent_fields(): void
    {
        $faq = Faq::factory()->create();

        $this->putJson("/api/admin/faqs/{$faq->id}", ['is_published' => true])->assertOk();

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'is_published' => true,
            'question_en' => $faq->question_en,
            'answer_ar' => $faq->answer_ar,
        ]);
    }

    public function test_update_cannot_blank_a_required_field(): void
    {
        $faq = Faq::factory()->create();

        $this->putJson("/api/admin/faqs/{$faq->id}", ['question_ar' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question_ar']);
    }

    public function test_delete_removes_the_row(): void
    {
        $faq = Faq::factory()->create();

        $this->deleteJson("/api/admin/faqs/{$faq->id}")->assertNoContent();

        $this->assertModelMissing($faq);
        $this->getJson("/api/admin/faqs/{$faq->id}")->assertNotFound()->assertExactJson(['message' => 'Not found.']);
    }

    public function test_index_lists_drafts_and_published_in_sort_order(): void
    {
        $second = Faq::factory()->published()->create(['sort_order' => 2]);
        $draft = Faq::factory()->create(['sort_order' => 1]);
        $tieA = Faq::factory()->published()->create(['sort_order' => 5]);
        $tieB = Faq::factory()->create(['sort_order' => 5]);

        $this->getJson('/api/admin/faqs')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$draft->id, $second->id, $tieA->id, $tieB->id])
            ->assertJsonPath('data.0.is_published', false);
    }

    public function test_show_includes_the_publish_status(): void
    {
        $faq = Faq::factory()->published()->create();

        $this->getJson("/api/admin/faqs/{$faq->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $faq->id)
            ->assertJsonPath('data.is_published', true);
    }
}
