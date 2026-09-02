<?php

namespace App\Domains\Publishing\Controllers;

use App\Domains\Publishing\Application\Services\SocialPublishingApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialPublishingController extends Controller
{
    public function __construct(
        private readonly SocialPublishingApplicationService $publishingService
    ) {}

    /**
     * List connected social accounts and available channel matrix.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $result = $this->publishingService->getConnectedAccounts($tenantContext);

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * Get OAuth authorization redirect URL.
     */
    public function getOAuthUrl(Request $request, string $platform): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $request->validate([
            'callback_url' => ['required', 'url'],
        ]);

        $url = $this->publishingService->getOAuthRedirectUrl(
            $tenantContext,
            $platform,
            $request->input('callback_url')
        );

        return response()->json([
            'data' => [
                'platform' => $platform,
                'authorization_url' => $url,
            ],
        ]);
    }

    /**
     * Handle OAuth code exchange callback.
     */
    public function handleCallback(Request $request, string $platform): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'callback_url' => ['required', 'url'],
        ]);

        $account = $this->publishingService->handleOAuthCallback(
            $tenantContext,
            $platform,
            $validated['code'],
            $validated['callback_url']
        );

        return response()->json([
            'message' => "Successfully connected " . ucfirst($platform) . " account.",
            'data' => $account,
        ], 201);
    }

    /**
     * Connect account via direct access token / custom API keys.
     */
    public function connectCustom(Request $request, string $platform): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $validated = $request->validate([
            'access_token' => ['required', 'string'],
            'account_id' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'account_username' => ['nullable', 'string'],
            'app_id' => ['nullable', 'string'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $account = $this->publishingService->connectCustomCredentials(
            $tenantContext,
            $platform,
            $validated
        );

        return response()->json([
            'message' => "Successfully connected " . ucfirst($platform) . " with custom API credentials.",
            'data' => $account,
        ], 201);
    }

    /**
     * Get available pages/accounts for a platform (e.g. /me/accounts).
     */
    public function getPages(Request $request, string $platform): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $userToken = $request->input('user_token');

        try {
            $pages = $this->publishingService->getAvailablePages($tenantContext, $platform, $userToken);

            return response()->json([
                'data' => [
                    'platform' => $platform,
                    'pages' => $pages,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get posts ready for direct publishing.
     */
    public function getReadyPosts(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $posts = $this->publishingService->getReadyPosts($tenantContext);

        return response()->json([
            'data' => $posts,
        ]);
    }

    /**
     * Health check for account token.
     */
    public function healthCheck(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $account = $this->publishingService->checkAccountHealth($tenantContext, $id);

        return response()->json([
            'message' => 'Account health check completed.',
            'data' => $account,
        ]);
    }

    /**
     * Disconnect/revoke a social channel.
     */
    public function disconnect(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $this->publishingService->disconnectAccount($tenantContext, $id);

        return response()->json([
            'message' => 'Social account disconnected successfully.',
        ]);
    }

    /**
     * Manually publish post immediately.
     */
    public function publishNow(Request $request, int $id): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $request->validate([
            'social_account_id' => ['nullable', 'integer'],
        ]);

        $job = $this->publishingService->publishNow(
            $tenantContext,
            $id,
            $request->input('social_account_id')
        );

        return response()->json([
            'message' => 'Post successfully published.',
            'data' => $job,
        ]);
    }

    /**
     * Get publishing history and queue jobs.
     */
    public function getJobs(Request $request): JsonResponse
    {
        $tenantContext = $request->attributes->get('tenant_context');

        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['pending', 'processing', 'published', 'failed', 'cancelled'])],
        ]);

        $jobs = $this->publishingService->getPublishingJobs($tenantContext, $filters);

        return response()->json($jobs);
    }
}
