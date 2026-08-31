<?php

namespace App\Domains\Identity\Controllers;

use App\Domains\Identity\Application\DTOs\LoginCredentialsData;
use App\Domains\Identity\Application\DTOs\RegisterUserData;
use App\Domains\Identity\Application\Services\AuthApplicationService;
use App\Domains\Identity\Application\Services\PasswordResetApplicationService;
use App\Domains\Shared\ValueObjects\Email;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthApplicationService $authService,
        private readonly PasswordResetApplicationService $passwordResetService
    ) {}

    /**
     * Register a new user and create an initial organization workspace atomically.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:100',
            'company_name' => 'nullable|string|min:2|max:100',
            'industry' => 'nullable|string|max:100',
        ]);

        $dto = new RegisterUserData(
            name: $validated['name'],
            email: new Email($validated['email']),
            password: $validated['password'],
            companyName: $validated['company_name'] ?? null,
            industry: $validated['industry'] ?? null
        );

        $result = $this->authService->register($dto);

        return ApiResponse::success(
            data: [
                'user' => [
                    'id' => $result->userId,
                    'name' => $result->name,
                    'email' => $result->email,
                ],
                'token' => $result->token,
                'token_type' => 'Bearer',
            ],
            meta: [
                'message' => 'Registration successful.',
            ],
            status: 201
        );
    }

    /**
     * Authenticate existing user and issue Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $dto = new LoginCredentialsData(
            email: new Email($validated['email']),
            password: $validated['password']
        );

        $result = $this->authService->login($dto);

        return ApiResponse::success(
            data: [
                'user' => [
                    'id' => $result->userId,
                    'name' => $result->name,
                    'email' => $result->email,
                ],
                'token' => $result->token,
                'token_type' => 'Bearer',
            ],
            meta: [
                'message' => 'Authentication successful.',
            ]
        );
    }

    /**
     * Invalidate current user session token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $this->authService->logout($user);
        }

        return ApiResponse::success(
            data: ['logged_out' => true],
            meta: ['message' => 'Logged out successfully.']
        );
    }

    /**
     * Request a password reset link (Generic anti-enumeration response).
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $result = $this->passwordResetService->requestPasswordReset($validated['email']);

        return ApiResponse::success(
            data: [
                'message' => $result['message'],
                'raw_token' => $result['raw_token'],
            ],
            meta: ['message' => $result['message']]
        );
    }

    /**
     * Reset password using a valid raw reset token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|max:100',
        ]);

        $this->passwordResetService->resetPassword(
            email: $validated['email'],
            rawToken: $validated['token'],
            newPassword: $validated['password']
        );

        return ApiResponse::success(
            data: null,
            meta: ['message' => 'Password reset successfully. You may now log in with your new credentials.']
        );
    }

    /**
     * Return authenticated user profile with active organization and DB-backed permissions.
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
            $membership = OrganizationMembershipModel::with(['organization', 'role'])
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
                $permissions = $context->permissions;
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
                'is_super_admin' => (bool) ($user->is_super_admin ?? false),
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'current_organization' => $currentOrg,
            'role' => $roleSlug,
            'permissions' => $permissions,
        ]);
    }
}
