<?php

namespace App\Social\Contracts;

interface SocialAccountServiceInterface
{
    /**
     * Connect a new social account via OAuth callback payload.
     *
     * @param string $brandId
     * @param string $platform
     * @param array<string, mixed> $authData
     * @return array<string, mixed>
     */
    public function connect(string $brandId, string $platform, array $authData): array;

    /**
     * Disconnect/revoke a social account.
     *
     * @param string $accountId
     * @return bool
     */
    public function disconnect(string $accountId): bool;

    /**
     * Refresh connection tokens for an account.
     *
     * @param string $accountId
     * @return bool
     */
    public function refresh(string $accountId): bool;

    /**
     * Run health check to verify token validity and permissions.
     *
     * @param string $accountId
     * @return array<string, mixed>
     */
    public function healthCheck(string $accountId): array;
}
