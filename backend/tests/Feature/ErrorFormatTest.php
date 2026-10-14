<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorFormatTest extends TestCase
{
    public function test_unknown_api_route_returns_a_generic_json_404(): void
    {
        $this->get('/api/does-not-exist')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Not found.']);
    }

    public function test_wrong_method_returns_json_405(): void
    {
        Route::post('/api/_only-post', fn () => 'ok')->middleware('api');

        $this->get('/api/_only-post')
            ->assertStatus(405)
            ->assertJsonStructure(['message']);
    }

    public function test_server_errors_hide_internal_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/_boom', fn () => throw new RuntimeException('secret internal detail'))->middleware('api');

        $response = $this->get('/api/_boom');

        $response->assertStatus(500)->assertExactJson(['message' => 'Server Error']);
        $this->assertStringNotContainsString('secret internal detail', $response->getContent());
    }

    public function test_oversized_request_body_returns_json_413(): void
    {
        $this->call('POST', '/api/does-not-exist', server: ['CONTENT_LENGTH' => (string) (PHP_INT_MAX >> 1)])
            ->assertStatus(413)
            ->assertExactJson(['message' => 'The request body is too large.']);
    }
}
