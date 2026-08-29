<?php

namespace App\Domains\Identity\Controllers;

use App\Domains\Identity\Application\DTOs\LoginCredentialsData;
use App\Domains\Identity\Application\DTOs\RegisterUserData;
use App\Domains\Identity\Application\Services\AuthApplicationService;
use App\Domains\Identity\Application\Services\PasswordResetApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthApplicationService $authService,
        private readonly PasswordResetApplicationService $passwordResetService
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
            data: $result->toArray(),
            meta: ['message' => 'User registered successfully'],
            status: 201
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
                data: $result->toArray(),
                meta: ['message' => 'Login successful']
            );
        } catch (AuthenticationException $e) {
            return ApiResponse::error(
                message: $e->getMessage() ?: 'Invalid credentials provided',
                code: 'INVALID_CREDENTIALS',
                status: 401
            );
        }
    }

    /**
     * Request password reset instructions without exposing user existence.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->passwordResetService->requestPasswordReset($validated['email']);

        return ApiResponse::success(
            data: $result,
            meta: ['message' => $result['message']]
        );
    }

    /**
     * Complete password reset using token and revoke existing session tokens.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $this->passwordResetService->resetPassword(
            email: $validated['email'],
            rawToken: $validated['token'],
            newPassword: $validated['password']
        );

        return ApiResponse::success(
            data: ['reset' => true],
            meta: ['message' => 'Password has been reset successfully. Please sign in with your new password.']
        );
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
            data: ['logged_out' => true],
            meta: ['message' => 'Successfully logged out']
        );
    }

    /**
     * Return authenticated user profile with active organization and permissions.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        /** @var TenantContext|null $context */
        $context = $request->attributes->get('tenant_context') ?? (app()->bound(TenantContext::class) ? app(TenantContext::class) : null);

        $currentOrg = null;
        $roleSlug = 'viewer';
        $permissions = [];

        if ($context) {
            $membership = OrganizationMembershipModel::with(['organization', 'role.permissions'])
                ->where('user_id', $user->id)
                ->where('organization_id', $context->organizationId)
                ->first();

            if ($membership) {
                $currentOrg = [
                    'id' => $membership->organization->id,
                    'name' => $membership->organization->name,
                    'slug' => $membership->organization->slug,
                    'type' => $membership->organization->type,
                    'status' => $membership->organization->status,
                ];
                $roleSlug = $membership->role->slug;
                $permissions = $context->userRole->permissions();
            }
        }

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale ?? 'en',
                'timezone' => $user->timezone ?? 'UTC',
                'status' => $user->status ?? 'active',
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'current_organization' => $currentOrg,
            'role' => $roleSlug,
            'permissions' => $permissions,
        ]);
    }
}
