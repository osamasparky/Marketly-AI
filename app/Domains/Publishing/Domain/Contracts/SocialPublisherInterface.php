<?php

namespace App\Domains\Publishing\Domain\Contracts;

use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;

interface SocialPublisherInterface
{
    /**
     * Generate the provider OAuth authorization URL with signed state.
     */
    public function getAuthorizationUrl(string $redirectUri, string $state): string;

    /**
     * Exchange temporary authorization code for access & refresh tokens.
     */
    public function exchangeAuthorizationCode(string $code, string $redirectUri): array;

    /**
     * Refresh an expired access token using refresh token.
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Perform live health check on access token against provider API.
     */
    public function healthCheck(SocialAccountModel $account): bool;

    /**
     * Publish structured post payload to provider API.
     * Returns array with: ['external_post_id', 'external_post_url', 'metrics']
     */
    public function publish(SocialAccountModel $account, array $payload): array;
}
