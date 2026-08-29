<?php

namespace App\Domains\Identity\Controllers;

use App\Domains\Identity\Application\DTOs\LoginCredentialsData;
use App\Domains\Identity\Application\DTOs\RegisterUserData;
use App\Domains\Identity\Application\Services\AuthApplicationService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthApplicationService $authService
    ) {}

    /**
     * Register a new user account.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $dto = new RegisterUserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password']
        );

        $result = $this->authService->register($dto);

        return ApiResponse::success(
            $result->toArray(),
            ['message' => 'User registered successfully'],
            201
        );
    }

    /**
     * Authenticate user credentials and return API token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $dto = new LoginCredentialsData(
                email: $validated['email'],
                password: $validated['password']
            );

            $result = $this->authService->login($dto);

            return ApiResponse::success(
                $result->toArray(),
                ['message' => 'Login successful']
            );
        } catch (AuthenticationException) {
            return ApiResponse::error(
                message: 'Invalid credentials provided',
                code: 'INVALID_CREDENTIALS',
                status: 401
            );
        }
    }

    /**
     * Revoke current API token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $this->authService->logout($user);
        }

        return ApiResponse::success(
            ['logged_out' => true],
            ['message' => 'Successfully logged out']
        );
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }
}
