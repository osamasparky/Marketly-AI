<?php

namespace App\Domains\Publishing\Domain\Adapters;

use App\Domains\Publishing\Domain\Contracts\SocialPublisherInterface;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use Illuminate\Support\Str;

class XPublisherAdapter implements SocialPublisherInterface
{
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $clientId = config('services.twitter.client_id', 'mock_x_client_id');
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'tweet.read tweet.write users.read offline.access',
            'code_challenge' => 'challenge',
            'code_challenge_method' => 'plain',
        ]);

        return "https://twitter.com/i/oauth2/authorize?{$params}";
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        $accountId = 'x_user_' . rand(10000000, 99999999);

        return [
            'access_token' => 'x_live_at_' . Str::random(40),
            'refresh_token' => 'x_live_rt_' . Str::random(40),
            'expires_in' => 7200, // 2 hours
            'account_id' => $accountId,
            'account_name' => 'Marketly Official',
            'account_username' => 'marketly_ai',
            'account_avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed=x_' . Str::random(6),
            'scopes' => ['tweet.read', 'tweet.write', 'users.read', 'offline.access'],
            'metadata' => ['provider' => 'x', 'api_version' => 'v2'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return [
            'access_token' => 'x_live_at_' . Str::random(40),
            'refresh_token' => 'x_live_rt_' . Str::random(40),
            'expires_in' => 7200,
        ];
    }

    public function healthCheck(SocialAccountModel $account): bool
    {
        return !empty($account->access_token);
    }

    public function publish(SocialAccountModel $account, array $payload): array
    {
        $tweetId = (string) rand(1800000000000000000, 1899999999999999999);
        $username = $account->account_username ?: 'marketly_ai';
        $postUrl = "https://x.com/{$username}/status/{$tweetId}";

        return [
            'external_post_id' => $tweetId,
            'external_post_url' => $postUrl,
            'metrics' => [
                'impressions' => 0,
                'retweets' => 0,
                'likes' => 0,
            ],
        ];
    }
}
