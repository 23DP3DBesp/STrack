<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_user_and_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'tester@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'tester@example.com',
            'role' => 'user',
        ]);
    }

    public function test_login_requires_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'valid@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'valid@example.com',
            'password' => 'Password123!',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
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
        config()->set('docbox.security.login_rate_per_minute', 2);

        User::factory()->create([
            'email' => 'rate@example.com',
            'password' => 'Password123!',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'rate@example.com',
            'password' => 'WrongPassword',
        ]);
        $this->postJson('/api/auth/login', [
            'email' => 'rate@example.com',
            'password' => 'WrongPassword',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'rate@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(429);
    }
}
