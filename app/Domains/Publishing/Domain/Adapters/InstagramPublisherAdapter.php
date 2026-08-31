<?php

namespace App\Domains\Publishing\Domain\Adapters;

use App\Domains\Publishing\Domain\Contracts\SocialPublisherInterface;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use Illuminate\Support\Str;

// STUB: not wired to a real API yet — see Phase 3 pattern.
class InstagramPublisherAdapter implements SocialPublisherInterface
{
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $appId = config('services.facebook.client_id', 'mock_fb_app_id');
        $params = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list',
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/v19.0/dialog/oauth?{$params}";
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        $igId = 'ig_pro_' . rand(10000000, 99999999);

        return [
            'access_token' => 'ig_live_at_' . Str::random(40),
            'refresh_token' => 'ig_live_rt_' . Str::random(40),
            'expires_in' => 5184000, // 60 days
            'account_id' => $igId,
            'account_name' => 'Marketly Visuals',
            'account_username' => 'marketly.app',
            'account_avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed=ig_' . Str::random(6),
            'scopes' => ['instagram_basic', 'instagram_content_publish', 'pages_show_list'],
            'metadata' => ['provider' => 'instagram', 'account_type' => 'business'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return [
            'access_token' => 'ig_live_at_' . Str::random(40),
            'refresh_token' => 'ig_live_rt_' . Str::random(40),
            'expires_in' => 5184000,
        ];
    }

    public function healthCheck(SocialAccountModel $account): bool
    {
        return !empty($account->access_token);
    }

    public function publish(SocialAccountModel $account, array $payload): array
    {
        $mediaCode = Str::random(11);
        $postUrl = "https://www.instagram.com/p/{$mediaCode}/";

        return [
            'external_post_id' => 'ig_media_' . rand(10000000, 99999999),
            'external_post_url' => $postUrl,
            'metrics' => [
                'impressions' => 0,
                'reach' => 0,
                'likes' => 0,
                'saved' => 0,
            ],
        ];
    }
}
