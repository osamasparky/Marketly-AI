<?php

namespace Tests\Feature;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_forgot_password_returns_generic_success_message_for_both_existing_and_non_existing_emails(): void
    {
        // Non-existing email
        $resNonExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);
        $resNonExisting->assertStatus(200);
        $resNonExisting->assertJsonPath('meta.message', 'If an account with that email exists, password reset instructions have been sent.');

        // Existing email
        UserModel::factory()->create(['email' => 'existing@example.com']);
        $resExisting = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'existing@example.com',
        ]);
        $resExisting->assertStatus(200);
        $resExisting->assertJsonPath('meta.message', 'If an account with that email exists, password reset instructions have been sent.');
    }

    public function test_reset_password_updates_password_and_revokes_old_tokens(): void
    {
        $user = UserModel::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $oldToken = $user->createToken('old_session')->plainTextToken;

        // 1. Request reset
        $forgotRes = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'user@example.com',
        ]);
        $rawToken = $forgotRes->json('data.raw_token');

        // 2. Submit reset
        $resetRes = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'user@example.com',
            'token' => $rawToken,
            'password' => 'NewSecurePassword123!',
        ]);
        $resetRes->assertStatus(200);

        // Reset auth guard state
        app('auth')->forgetGuards();

        // 3. Verify old token is revoked
        $meRes = $this->withHeader('Authorization', "Bearer {$oldToken}")->getJson('/api/v1/me');
        $meRes->assertStatus(401);

        // 4. Verify login with new password succeeds
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'NewSecurePassword123!',
        ]);
        $loginRes->assertStatus(200);
    }
}
