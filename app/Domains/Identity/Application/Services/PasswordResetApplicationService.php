<?php

namespace App\Domains\Identity\Application\Services;

use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PasswordResetApplicationService
{
    private const TOKEN_EXPIRE_MINUTES = 60;

    public function __construct(
        private readonly AuditApplicationService $auditService
    ) {}

    /**
     * Request a password reset with zero email enumeration information disclosure.
     *
     * @return array{message: string, raw_token: ?string}
     */
    public function requestPasswordReset(string $email): array
    {
        $sanitizedEmail = strtolower(trim($email));
        $user = UserModel::where('email', $sanitizedEmail)->first();

        $rawToken = null;

        if ($user) {
            $rawToken = Str::random(64);
            $tokenHash = hash('sha256', $rawToken);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $sanitizedEmail],
                [
                    'token' => $tokenHash,
                    'created_at' => now(),
                ]
            );

            $this->auditService->log(
                action: 'user.password_reset_requested',
                userId: $user->id,
                metadata: ['email_fingerprint' => hash('sha256', $sanitizedEmail)]
            );
        }

        return [
            'message' => 'If an account with that email exists, password reset instructions have been sent.',
            'raw_token' => $rawToken,
        ];
    }

    /**
     * Alias for requestPasswordReset.
     */
    public function sendResetLink(string $email): array
    {
        return $this->requestPasswordReset($email);
    }

    /**
     * Complete password reset using token and revoke existing session tokens.
     */
    public function resetPassword(string $email, string $rawToken, string $newPassword): bool
    {
        $sanitizedEmail = strtolower(trim($email));
        $tokenHash = hash('sha256', trim($rawToken));

        $record = DB::table('password_reset_tokens')
            ->where('email', $sanitizedEmail)
            ->where('token', $tokenHash)
            ->first();

        if (!$record) {
            throw new InvalidArgumentException('Invalid or expired password reset token.');
        }

        // Check 60-minute expiration
        if (now()->subMinutes(self::TOKEN_EXPIRE_MINUTES)->gt($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $sanitizedEmail)->delete();
            throw new InvalidArgumentException('Password reset token has expired.');
        }

        $user = UserModel::where('email', $sanitizedEmail)->firstOrFail();

        DB::transaction(function () use ($user, $sanitizedEmail, $newPassword) {
            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // Revoke all existing personal access tokens for absolute session termination
            DB::table('personal_access_tokens')
                ->where('tokenable_id', $user->id)
                ->delete();

            // Invalidate the reset token
            DB::table('password_reset_tokens')->where('email', $sanitizedEmail)->delete();

            $this->auditService->log(
                action: 'user.password_reset_completed',
                userId: $user->id,
                metadata: ['email_fingerprint' => hash('sha256', $sanitizedEmail)]
            );
        });

        return true;
    }
}
