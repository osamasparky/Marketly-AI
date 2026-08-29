<?php

namespace App\Domains\Identity\Application\Services;

use App\Domains\Identity\Application\DTOs\AuthResultData;
use App\Domains\Identity\Application\DTOs\LoginCredentialsData;
use App\Domains\Identity\Application\DTOs\RegisterUserData;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApplicationService
{
    /**
     * Register a new user and generate access token.
     */
    public function register(RegisterUserData $data): AuthResultData
    {
        $existing = User::where('email', $data->email->value())->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        $user = User::create([
            'name' => $data->name,
            'email' => $data->email->value(),
            'password' => Hash::make($data->password),
        ]);

        $token = $user->createToken('marketly_auth_token')->plainTextToken;

        return new AuthResultData(
            userId: $user->id,
            name: $user->name,
            email: $user->email,
            token: $token,
            createdAt: $user->created_at?->toIso8601String()
        );
    }

    /**
     * Authenticate credentials and generate access token.
     */
    public function login(LoginCredentialsData $data): AuthResultData
    {
        $user = User::where('email', $data->email->value())->first();

        if (!$user || !Hash::check($data->password, $user->password)) {
            throw new AuthenticationException('Invalid credentials provided');
        }

        $token = $user->createToken('marketly_auth_token')->plainTextToken;

        return new AuthResultData(
            userId: $user->id,
            name: $user->name,
            email: $user->email,
            token: $token,
            createdAt: $user->created_at?->toIso8601String()
        );
    }

    /**
     * Invalidate/revoke current user token.
     */
    public function logout(User $user): bool
    {
        $user->currentAccessToken()?->delete();
        return true;
    }
}
