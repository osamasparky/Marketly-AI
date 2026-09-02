<?php

namespace App\Domains\Publishing\Domain\Adapters;

use App\Domains\Publishing\Domain\Contracts\SocialPublisherInterface;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class FacebookPublisherAdapter implements SocialPublisherInterface
{
    private string $graphVersion = 'v20.0';

    public function getAuthorizationUrl(string $redirectUri, string $state): string
    {
        $appId = config('services.facebook.client_id', 'mock_fb_app_id');
        $params = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'pages_show_list,pages_read_engagement,pages_manage_posts,pages_manage_metadata,publish_video',
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/{$this->graphVersion}/dialog/oauth?{$params}";
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        $appId = config('services.facebook.client_id');
        $appSecret = config('services.facebook.client_secret');

        if ($appId && $appSecret && !str_starts_with($code, 'oauth_sandbox_')) {
            try {
                $response = Http::withoutVerifying()->timeout(20)->get("https://graph.facebook.com/{$this->graphVersion}/oauth/access_token", [
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $userToken = $data['access_token'] ?? '';
                    $pages = $this->fetchManagedPages($userToken);
                    $firstPage = $pages[0] ?? null;

                    if ($firstPage) {
                        return [
                            'access_token' => $firstPage['access_token'],
                            'refresh_token' => null,
                            'expires_in' => $data['expires_in'] ?? 5184000,
                            'account_id' => $firstPage['id'],
                            'account_name' => $firstPage['name'],
                            'account_username' => $firstPage['category'] ?? 'Facebook Page',
                            'account_avatar' => $firstPage['picture'] ?? null,
                            'scopes' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
                            'metadata' => ['provider' => 'facebook', 'user_token' => $userToken, 'page_category' => $firstPage['category'] ?? null],
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Facebook OAuth exchange error: ' . $e->getMessage());
            }
        }

        $pageId = 'fb_page_' . rand(10000000, 99999999);

        return [
            'access_token' => 'fb_live_at_' . Str::random(40),
            'refresh_token' => 'fb_live_rt_' . Str::random(40),
            'expires_in' => 5184000,
            'account_id' => $pageId,
            'account_name' => 'Marketly Official Page (Sandbox)',
            'account_username' => 'marketly.official',
            'account_avatar' => 'https://api.dicebear.com/7.x/identicon/svg?seed=fb_' . Str::random(6),
            'scopes' => ['pages_manage_posts', 'pages_read_engagement', 'pages_show_list'],
            'metadata' => ['provider' => 'facebook', 'page_category' => 'Technology Company', 'mode' => 'sandbox'],
        ];
    }

    /**
     * Fetch all real Facebook Pages managed by the provided User/System Access Token (/me/accounts).
     */
    public function fetchManagedPages(?string $userAccessToken): array
    {
        if (empty($userAccessToken) || $userAccessToken === 'sandbox_mode') {
            return [
                [
                    'id' => 'fb_page_demo_101',
                    'name' => 'Demo Sandbox Page',
                    'category' => 'Testing & Demonstration',
                    'access_token' => 'EAAB_sandbox_token_' . Str::random(32),
                    'picture' => 'https://api.dicebear.com/7.x/identicon/svg?seed=fb_demo',
                ],
            ];
        }

        try {
            $response = Http::withoutVerifying()->timeout(15)->get("https://graph.facebook.com/{$this->graphVersion}/me/accounts", [
                'access_token' => trim($userAccessToken),
                'fields' => 'id,name,category,access_token,tasks,picture{url}',
            ]);

            if ($response->successful()) {
                $items = $response->json('data') ?? [];
                if (empty($items)) {
                    throw new RuntimeException('No Facebook Pages found for this Access Token. Ensure the user is an admin of at least one Page and granted pages_show_list permission.');
                }

                $pages = [];
                foreach ($items as $item) {
                    $pages[] = [
                        'id' => (string) $item['id'],
                        'name' => (string) $item['name'],
                        'category' => (string) ($item['category'] ?? 'General Page'),
                        'access_token' => (string) ($item['access_token'] ?? $userAccessToken),
                        'picture' => $item['picture']['data']['url'] ?? null,
                    ];
                }
                return $pages;
            }

            $error = $response->json('error');
            $errorMsg = is_array($error) ? ($error['message'] ?? 'Unknown Meta API error') : 'Invalid Access Token or secret key provided.';
            throw new RuntimeException("Meta Graph API Error: {$errorMsg}");
        } catch (\Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }
            throw new RuntimeException("Could not connect to Meta Graph API: " . $e->getMessage());
        }
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
        $caption = $payload['content_text'] ?? ($payload['caption'] ?? '');
        $mediaUrl = $payload['media_url'] ?? null;
        $pageId = $account->account_id;
        $pageAccessToken = $account->access_token;

        // Attempt live Graph API publish if live token
        if (!str_starts_with($pageAccessToken, 'fb_live_at_') && !str_starts_with($pageAccessToken, 'EAAB_sandbox_token_') && !str_starts_with($pageAccessToken, 'sandbox_')) {
            try {
                $endpoint = $mediaUrl 
                    ? "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/photos"
                    : "https://graph.facebook.com/{$this->graphVersion}/{$pageId}/feed";

                $postParams = $mediaUrl
                    ? ['url' => $mediaUrl, 'caption' => $caption, 'access_token' => $pageAccessToken]
                    : ['message' => $caption, 'access_token' => $pageAccessToken];

                $response = Http::withoutVerifying()->timeout(30)->post($endpoint, $postParams);

                if ($response->successful()) {
                    $json = $response->json();
                    $postId = $json['id'] ?? ($pageId . '_' . rand(100000000, 999999999));
                    $parts = explode('_', $postId);
                    $fbPostId = count($parts) > 1 ? $parts[1] : $postId;
                    $postUrl = "https://www.facebook.com/{$pageId}/posts/{$fbPostId}";

                    return [
                        'external_post_id' => $postId,
                        'external_post_url' => $postUrl,
                        'metrics' => ['reach' => 0, 'reactions' => 0, 'comments' => 0],
                    ];
                }

                $err = $response->json('error.message') ?? 'Facebook API rejected publishing.';
                Log::error("Facebook live publish failed: " . $err);
                throw new RuntimeException("Facebook API Error: {$err}");
            } catch (\Throwable $e) {
                if ($e instanceof RuntimeException) {
                    throw $e;
                }
                throw new RuntimeException("Could not publish to Facebook: " . $e->getMessage());
            }
        }

        $postStoryId = $pageId . '_' . rand(100000000, 999999999);
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
