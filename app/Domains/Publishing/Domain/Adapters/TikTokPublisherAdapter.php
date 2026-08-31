<?php

namespace App\Domains\Publishing\Domain\Adapters;

use App\Domains\Publishing\Domain\Contracts\SocialPublisherInterface;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use Illuminate\Support\Str;

class TikTokPublisherAdapter implements SocialPublisherInterface
{
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $clientKey = config('services.tiktok.client_key', 'mock_tiktok_key');
        $params = http_build_query([
            'client_key' => $clientKey,
            'scope' => 'user.info.basic,video.publish,video.upload',
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return "https://www.tiktok.com/v2/auth/authorize/?{$params}";
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        $openId = 'tt_open_' . rand(10000000, 99999999);

        return [
            'access_token' => 'tt_live_at_' . Str::random(40),
            'refresh_token' => 'tt_live_rt_' . Str::random(40),
            'expires_in' => 86400, // 24 hours
            'account_id' => $openId,
            'account_name' => 'Marketly TikTok Studio',
            'account_username' => 'marketly_tok',
            'account_avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed=tt_' . Str::random(6),
            'scopes' => ['user.info.basic', 'video.publish', 'video.upload'],
            'metadata' => ['provider' => 'tiktok', 'creator_type' => 'business'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return [
            'access_token' => 'tt_live_at_' . Str::random(40),
            'refresh_token' => 'tt_live_rt_' . Str::random(40),
            'expires_in' => 86400,
        ];
    }

    public function healthCheck(SocialAccountModel $account): bool
    {
        return !empty($account->access_token);
    }

    public function publish(SocialAccountModel $account, array $payload): array
    {
        $videoId = 'tt_vid_' . rand(100000000, 999999999);
        $postUrl = "https://www.tiktok.com/@{$account->account_username}/video/{$videoId}";

        return [
            'external_post_id' => $videoId,
            'external_post_url' => $postUrl,
            'metrics' => [
                'views' => 0,
                'likes' => 0,
                'shares' => 0,
            ],
        ];
    }
}
