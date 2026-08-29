<?php

namespace App\Domains\Identity\Application\Services;

use App\Domains\Identity\Application\DTOs\AuthResultData;
use App\Domains\Identity\Application\DTOs\LoginCredentialsData;
use App\Domains\Identity\Application\DTOs\RegisterUserData;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Application\Services\OrganizationApplicationService;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApplicationService
{
    public function __construct(
        private readonly OrganizationApplicationService $orgService,
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * Register a new user, create their initial default organization tenant, and generate access token.
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
            'status' => 'active',
            'locale' => 'en',
            'timezone' => 'UTC',
        ]);

        // Auto-create initial default organization tenant
        $this->orgService->createOrganization(
            user: $user,
            name: "{$user->name}'s Workspace",
            type: 'business',
            defaultLocale: 'en',
            timezone: 'UTC'
        );

        $token = $user->createToken('marketly_auth_token')->plainTextToken;

        $this->auditService->log(
            action: 'user.registered',
            userId: $user->id,
            metadata: ['email' => $user->email]
        );

        return new AuthResultData(
            userId: $user->id,
            name: $user->name,
            email: $user->email,
            token: $token,
            createdAt: $user->created_at?->toIso8601String()
        );
    }

    /**
     * Authenticate credentials, record last_login_at, and generate access token.
     */
    public function login(LoginCredentialsData $data): AuthResultData
    {
        $user = User::where('email', $data->email->value())->first();

        if (!$user || !Hash::check($data->password, $user->password)) {
            $this->auditService->log(
                action: 'user.login_failed',
                metadata: ['email' => $data->email->value()]
            );

            throw new AuthenticationException('Invalid credentials provided');
        }

        if ($user->status !== 'active') {
            throw new AuthenticationException('Account is not active.');
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('marketly_auth_token')->plainTextToken;

        $this->auditService->log(
            action: 'user.login',
            userId: $user->id,
            metadata: ['email' => $user->email]
        );

        return new AuthResultData(
            userId: $user->id,
            name: $user->name,
            email: $user->email,
            token: $token,
            createdAt: $user->created_at?->toIso8601String()
        );
    }

    /**
     * Invalidate/revoke current user token and record logout event.
     */
    public function logout(User $user): bool
    {
        $this->auditService->log(
            action: 'user.logout',
            userId: $user->id
        );

        $user->currentAccessToken()?->delete();
        return true;
    }
}
