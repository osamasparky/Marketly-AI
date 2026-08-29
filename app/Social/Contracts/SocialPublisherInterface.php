<?php

namespace App\Social\Contracts;

use App\Social\Contracts\DTOs\PublishPayload;
use App\Social\Contracts\DTOs\PublishResult;
use App\Social\Contracts\DTOs\SocialMetricSnapshot;

interface SocialPublisherInterface
{
    /**
     * Generate platform OAuth authorization URL.
     *
     * @param string $state
     * @param array<string, mixed> $options
     * @return string
     */
    public function getAuthorizationUrl(string $state, array $options = []): string;

    /**
     * Exchange authorization code for token payload.
     *
     * @param string $code
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function exchangeAuthorizationCode(string $code, array $options = []): array;

    /**
     * Refresh OAuth access token.
     *
     * @param string $refreshToken
     * @return array<string, mixed>
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Fetch connected external account profile details.
     *
     * @param string $accessToken
     * @return array<string, mixed>
     */
    public function getAccount(string $accessToken): array;

    /**
     * Validate media assets for platform compatibility.
     *
     * @param array<int, string> $mediaUrls
     * @param string $mediaType
     * @return array<string, mixed>
     */
    public function validateMedia(array $mediaUrls, string $mediaType): array;

    /**
     * Publish post payload to external platform.
     *
     * @param string $accessToken
     * @param PublishPayload $payload
     * @return PublishResult
     */
    public function publish(string $accessToken, PublishPayload $payload): PublishResult;

    /**
     * Delete post by external post ID if supported.
     *
     * @param string $accessToken
     * @param string $externalPostId
     * @return bool
     */
    public function delete(string $accessToken, string $externalPostId): bool;

    /**
     * Fetch recent metrics for a published post.
     *
     * @param string $accessToken
     * @param string $externalPostId
     * @return SocialMetricSnapshot
     */
    public function getPostMetrics(string $accessToken, string $externalPostId): SocialMetricSnapshot;
}
