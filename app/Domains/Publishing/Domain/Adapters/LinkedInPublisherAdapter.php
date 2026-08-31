<?php

namespace App\Domains\Publishing\Domain\Adapters;

use App\Domains\Publishing\Domain\Contracts\SocialPublisherInterface;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use Illuminate\Support\Str;

class LinkedInPublisherAdapter implements SocialPublisherInterface
{
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $clientId = config('services.linkedin.client_id', 'mock_linkedin_client_id');
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'openid profile email w_member_social',
        ]);

        return "https://www.linkedin.com/oauth/v2/authorization?{$params}";
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        // Sandbox / provider token exchange
        $accountId = 'urn:li:person:' . Str::random(10);

        return [
            'access_token' => 'ln_live_at_' . Str::random(40),
            'refresh_token' => 'ln_live_rt_' . Str::random(40),
            'expires_in' => 5184000, // 60 days
            'account_id' => $accountId,
            'account_name' => 'Marketly Brand Admin',
            'account_username' => 'marketly_corp',
            'account_avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed=linkedin_' . Str::random(6),
            'scopes' => ['openid', 'profile', 'email', 'w_member_social'],
            'metadata' => ['provider' => 'linkedin', 'api_version' => 'v2'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return [
            'access_token' => 'ln_live_at_' . Str::random(40),
            'refresh_token' => 'ln_live_rt_' . Str::random(40),
            'expires_in' => 5184000,
        ];
    }

    public function healthCheck(SocialAccountModel $account): bool
    {
        return !empty($account->access_token);
    }

    public function publish(SocialAccountModel $account, array $payload): array
    {
        $postId = 'urn:li:share:' . rand(100000000, 999999999);
        $shareUrl = "https://www.linkedin.com/feed/update/{$postId}";

        return [
            'external_post_id' => $postId,
            'external_post_url' => $shareUrl,
            'metrics' => [
                'impressions' => 0,
                'clicks' => 0,
                'reactions' => 0,
            ],
        ];
    }
}
