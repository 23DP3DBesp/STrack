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
            'login' => 'tester',
            'password' => 'Password123!',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'login'], 'token']);

        $this->assertDatabaseHas('users', [
            'login' => 'tester',
        ]);
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
}
