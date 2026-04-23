<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_unverified_user_and_requests_verification_email(): void
    {
        config()->set('services.mailtrap.token', 'test-token');
        config()->set('services.mailtrap.sender_email', 'noreply@example.com');

        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response([
                'success' => true,
                'message_ids' => ['mailtrap-message-id'],
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'login' => 'tester',
            'email' => 'tester@example.com',
            'password' => 'Password123!A',
            'password_confirmation' => 'Password123!A',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.login', 'tester')
            ->assertJsonPath('user.email', 'tester@example.com')
            ->assertJsonPath('verification_email_sent', true);

        $this->assertDatabaseHas('users', [
            'login' => 'tester',
            'email' => 'tester@example.com',
            'email_verified_at' => null,
        ]);

        Http::assertSentCount(1);
    }

    public function test_login_requires_valid_credentials(): void
    {
        User::factory()->create([
            'login' => 'valid-driver',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'valid-driver',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'login' => 'valid-driver',
            'email' => 'driver@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'valid-driver',
            'password' => 'Password123!',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'login'], 'token']);
    }

    public function test_login_is_blocked_until_email_is_verified(): void
    {
        User::factory()->create([
            'login' => 'pending-driver',
            'email' => 'pending@example.com',
            'email_verified_at' => null,
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'pending-driver',
            'password' => 'Password123!',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('code', 'email_not_verified');
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create([
            'login' => 'rate-driver',
            'password' => 'Password123!',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/login', [
                'login' => 'rate-driver',
                'password' => 'WrongPassword',
            ]);
        }

        $response = $this->postJson('/api/auth/login', [
            'login' => 'rate-driver',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(429);
    }

    public function test_user_can_verify_email_from_signed_link(): void
    {
        $user = User::factory()->create([
            'email' => 'verifyme@example.com',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('http://localhost:5173/verify-email?status=verified&email=verifyme%40example.com');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_resend_verification_email_returns_generic_message(): void
    {
        config()->set('services.mailtrap.token', 'test-token');
        config()->set('services.mailtrap.sender_email', 'noreply@example.com');

        Http::fake([
            'https://send.api.mailtrap.io/api/send' => Http::response(['success' => true], 200),
        ]);

        User::factory()->create([
            'login' => 'resend-me',
            'email' => 'resend@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/email/verification-notification', [
            'email' => 'resend@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'If the account exists and is not verified, a verification email has been sent.');

        Http::assertSentCount(1);
    }
}
