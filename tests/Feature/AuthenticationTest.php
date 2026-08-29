<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Osama Marketer',
            'email' => 'osama@marketly.ai',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                ],
                'meta' => [
                    'message',
                    'timestamp',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'osama@marketly.ai',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'duplicate@marketly.ai',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'duplicate@marketly.ai',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'VALIDATION_ERROR',
            ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'tester@marketly.ai',
            'password' => bcrypt('ValidPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'tester@marketly.ai',
            'password' => 'ValidPassword123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'tester@marketly.ai',
            'password' => bcrypt('ValidPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'tester@marketly.ai',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 'INVALID_CREDENTIALS',
            ]);
    }

    public function test_authenticated_user_can_access_me_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        // Test /api/v1/me
        $meResponse = $this->getJson('/api/v1/me', [
            'Authorization' => "Bearer {$token}",
        ]);

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.user.email', $user->email);

        // Test /api/v1/auth/logout
        $logoutResponse = $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('data.logged_out', true);
    }

    public function test_unauthenticated_request_returns_standard_error(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'code' => 'UNAUTHENTICATED',
            ]);
    }
}
