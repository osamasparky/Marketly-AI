<?php

namespace App\Domains\Publishing\Domain\Adapters;

use App\Domains\Publishing\Domain\Contracts\SocialPublisherInterface;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use Illuminate\Support\Str;

class FacebookPublisherAdapter implements SocialPublisherInterface
{
    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $appId = config('services.facebook.client_id', 'mock_fb_app_id');
        $params = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'pages_manage_posts,pages_read_engagement,pages_show_list',
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/v19.0/dialog/oauth?{$params}";
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        $pageId = 'fb_page_' . rand(10000000, 99999999);

        return [
            'access_token' => 'fb_live_at_' . Str::random(40),
            'refresh_token' => 'fb_live_rt_' . Str::random(40),
            'expires_in' => 5184000, // 60 days
            'account_id' => $pageId,
            'account_name' => 'Marketly AI Page',
            'account_username' => 'marketly.official',
            'account_avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed=fb_' . Str::random(6),
            'scopes' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
            'metadata' => ['provider' => 'facebook', 'page_category' => 'Software Company'],
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return [
            'access_token' => 'fb_live_at_' . Str::random(40),
            'refresh_token' => 'fb_live_rt_' . Str::random(40),
            'expires_in' => 5184000,
        ];
    }

    public function healthCheck(SocialAccountModel $account): bool
    {
        return !empty($account->access_token);
    }

    public function publish(SocialAccountModel $account, array $payload): array
    {
        $postStoryId = $account->account_id . '_' . rand(100000000, 999999999);
        $postUrl = "https://facebook.com/{$postStoryId}";

        return [
            'external_post_id' => $postStoryId,
            'external_post_url' => $postUrl,
            'metrics' => [
                'reach' => 0,
                'reactions' => 0,
                'comments' => 0,
            ],
        ];
    }
}
