<?php

namespace App\Domains\Identity\Application\Services;

use App\Domains\Identity\Application\DTOs\AuthResultData;
use App\Domains\Identity\Application\DTOs\LoginCredentialsData;
use App\Domains\Identity\Application\DTOs\RegisterUserData;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Application\Services\OrganizationApplicationService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApplicationService
{
    public function __construct(
        private readonly OrganizationApplicationService $orgService,
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * Register a new user and create their initial organization atomically inside a single DB transaction.
     * Tokens are generated strictly after the transaction commits successfully.
     */
    public function register(RegisterUserData $data): AuthResultData
    {
        $existing = UserModel::where('email', $data->email->value())->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        /** @var UserModel $user */
        $user = DB::transaction(function () use ($data) {
            $createdUser = UserModel::create([
                'name' => $data->name,
                'email' => $data->email->value(),
                'password' => Hash::make($data->password),
                'status' => 'active',
                'locale' => 'en',
                'timezone' => 'UTC',
            ]);

            // Auto-create initial default organization tenant atomically
            $this->orgService->createOrganization(
                user: $createdUser,
                name: "{$createdUser->name}'s Workspace",
                type: 'business',
                defaultLocale: 'en',
                timezone: 'UTC'
            );

            return $createdUser;
        });

        // Generate token strictly AFTER database transaction commits
        $token = $user->createToken('marketly_auth_token')->plainTextToken;

        $this->auditService->log(
            action: 'user.registered',
            userId: $user->id,
            metadata: ['email_fingerprint' => hash('sha256', $user->email)]
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
        $user = UserModel::where('email', $data->email->value())->first();

        if (!$user || !Hash::check($data->password, $user->password)) {
            $this->auditService->log(
                action: 'user.login_failed',
                metadata: ['email_fingerprint' => hash('sha256', $data->email->value())]
            );

            // Generic error message without revealing email existence
            throw new AuthenticationException('Invalid credentials provided.');
        }

        if ($user->status !== 'active') {
            throw new AuthenticationException('Account is not active.');
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('marketly_auth_token')->plainTextToken;

        $this->auditService->log(
            action: 'user.login',
            userId: $user->id,
            metadata: ['email_fingerprint' => hash('sha256', $user->email)]
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
    public function logout(UserModel $user): bool
    {
        $this->auditService->log(
            action: 'user.logout',
            userId: $user->id
        );

        $user->currentAccessToken()?->delete();
        return true;
    }
}
