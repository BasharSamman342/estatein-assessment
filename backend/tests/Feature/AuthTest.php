<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_FAILURE = 'These credentials do not match our records.';

    public function test_bad_password_is_401_with_the_generic_message_and_no_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => self::GENERIC_FAILURE]);
    }

    public function test_unknown_email_gets_the_same_response_as_a_bad_password(): void
    {
        $this->postJson('/api/login', ['email' => 'nobody@estatein.test', 'password' => 'password'])
            ->assertUnauthorized()
            ->assertExactJson(['message' => self::GENERIC_FAILURE]);
    }

    public function test_missing_credentials_are_validation_errors(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_good_login_returns_a_token_that_works_on_me(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertOk();

        $this->assertSame(['token', 'user'], array_keys($response->json()));
        $this->assertNotEmpty($response->json('token'));
        $this->assertSame(['id' => $user->id, 'name' => $user->name, 'email' => $user->email], $response->json('user'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_me_returns_the_user_for_a_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $token = $this->login($user->email, 'secret-pass');

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertExactJson(['data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]]);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $token = $this->login($user->email, 'secret-pass');

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The guard caches the resolved user for the lifetime of the test app.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $token = $this->login($user->email, 'secret-pass');

        $this->travel(config('sanctum.expiration') + 1)->minutes();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }

    public function test_the_sixth_login_attempt_in_a_minute_is_throttled(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', ['email' => 'nobody@estatein.test', 'password' => 'x'])->assertUnauthorized();
        }

        $this->postJson('/api/login', ['email' => 'nobody@estatein.test', 'password' => 'x'])->assertTooManyRequests();
    }

    public function test_guest_gets_json_401_even_without_an_accept_header(): void
    {
        $this->get('/api/me')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    private function login(string $email, string $password): string
    {
        return $this->postJson('/api/login', ['email' => $email, 'password' => $password])
            ->assertOk()
            ->json('token');
    }
}
