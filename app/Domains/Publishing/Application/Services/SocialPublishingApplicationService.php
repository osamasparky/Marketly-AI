<?php

namespace App\Domains\Publishing\Application\Services;

use App\Domains\Billing\Domain\Services\EntitlementService;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Publishing\Domain\Services\SocialPublisherFactory;
use App\Domains\Publishing\Infrastructure\Persistence\Models\PublishingJobModel;
use App\Domains\Publishing\Infrastructure\Persistence\Models\SocialAccountModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SocialPublishingApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly EntitlementService $entitlementService,
        private readonly SocialPublisherFactory $publisherFactory
    ) {}

    /**
     * Get all connected social accounts for the organization.
     */
    public function getConnectedAccounts(TenantContext $context): array
    {
        TenantIsolationGuard::assertPermission($context, 'social.view');

        $accounts = SocialAccountModel::where('organization_id', $context->organizationId)
            ->where('is_active', true)
            ->get();

        $supportedPlatforms = ['linkedin', 'instagram', 'x', 'facebook', 'tiktok'];
        $matrix = [];

        foreach ($supportedPlatforms as $platform) {
            $connected = $accounts->where('platform', $platform)->first();
            $matrix[] = [
                'platform' => $platform,
                'is_connected' => (bool) $connected,
                'account' => $connected,
            ];
        }

        return [
            'total_connected' => $accounts->count(),
            'channels' => $matrix,
            'accounts' => $accounts,
        ];
    }

    /**
     * Generate signed OAuth authorization URL for given platform.
     */
    public function getOAuthRedirectUrl(TenantContext $context, string $platform, string $callbackUrl): string
    {
        TenantIsolationGuard::assertPermission($context, 'social.connect');

        $adapter = $this->publisherFactory->make($platform);
        $state = base64_encode(json_encode([
            'org_id' => $context->organizationId,
            'user_id' => $context->userId,
            'platform' => $platform,
            'nonce' => Str::random(16),
            'timestamp' => time(),
        ]));

        return $adapter->getAuthorizationUrl($callbackUrl, $state);
    }

    /**
     * Complete OAuth handshake and persist connected social account.
     */
    public function handleOAuthCallback(TenantContext $context, string $platform, string $code, string $callbackUrl): SocialAccountModel
    {
        TenantIsolationGuard::assertPermission($context, 'social.connect');

        $adapter = $this->publisherFactory->make($platform);
        $tokenData = $adapter->exchangeAuthorizationCode($code, $callbackUrl);

        $expiresAt = isset($tokenData['expires_in'])
            ? Carbon::now()->addSeconds($tokenData['expires_in'])
            : Carbon::now()->addDays(60);

        $account = SocialAccountModel::updateOrCreate(
            [
                'organization_id' => $context->organizationId,
                'platform' => strtolower($platform),
                'account_id' => $tokenData['account_id'],
            ],
            [
                'user_id' => $context->userId,
                'account_name' => $tokenData['account_name'] ?? ucfirst($platform) . ' Account',
                'account_username' => $tokenData['account_username'] ?? null,
                'account_avatar' => $tokenData['account_avatar'] ?? null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => $expiresAt,
                'scopes' => $tokenData['scopes'] ?? [],
                'is_active' => true,
                'health_status' => 'healthy',
                'last_health_check_at' => Carbon::now(),
                'metadata' => $tokenData['metadata'] ?? [],
            ]
        );

        $this->auditService->log(
            action: 'social.account_connected',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'social_account',
            entityId: (string) $account->id
        );

        return $account;
    }

    /**
     * Check health status of a connected account.
     */
    public function checkAccountHealth(TenantContext $context, int $accountId): SocialAccountModel
    {
        TenantIsolationGuard::assertPermission($context, 'social.view');

        $account = SocialAccountModel::where('organization_id', $context->organizationId)
            ->where('id', $accountId)
            ->firstOrFail();

        $adapter = $this->publisherFactory->make($account->platform);
        $isHealthy = $adapter->healthCheck($account);

        $account->update([
            'health_status' => $isHealthy ? 'healthy' : 'expired',
            'last_health_check_at' => Carbon::now(),
        ]);

        return $account;
    }

    /**
     * Disconnect/revoke a social channel.
     */
    public function disconnectAccount(TenantContext $context, int $accountId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'social.disconnect');

        $account = SocialAccountModel::where('organization_id', $context->organizationId)
            ->where('id', $accountId)
            ->firstOrFail();

        $account->update([
            'is_active' => false,
            'health_status' => 'revoked',
        ]);

        $this->auditService->log(
            action: 'social.account_disconnected',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'social_account',
            entityId: (string) $account->id
        );

        return true;
    }

    /**
     * Immediately publish a post to its primary platform or specified connected account.
     */
    public function publishNow(TenantContext $context, int $postId, ?int $accountId = null): PublishingJobModel
    {
        TenantIsolationGuard::assertPermission($context, 'social.publish');

        $post = ContentPostModel::with('variations')
            ->where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        // Resolve target social account
        $socialAccount = $accountId
            ? SocialAccountModel::where('organization_id', $context->organizationId)->where('id', $accountId)->firstOrFail()
            : SocialAccountModel::where('organization_id', $context->organizationId)
                ->where('platform', $post->primary_platform)
                ->where('is_active', true)
                ->first();

        if (!$socialAccount) {
            // Auto-provision sandbox connector if testing without external OAuth
            $socialAccount = SocialAccountModel::firstOrCreate(
                [
                    'organization_id' => $context->organizationId,
                    'platform' => $post->primary_platform,
                    'account_id' => 'sandbox_' . $post->primary_platform . '_' . $context->organizationId,
                ],
                [
                    'user_id' => $context->userId,
                    'account_name' => ucfirst($post->primary_platform) . ' Channel',
                    'account_username' => 'brand_' . $context->organizationId,
                    'access_token' => 'sandbox_token_' . Str::random(32),
                    'is_active' => true,
                    'health_status' => 'healthy',
                ]
            );
        }

        // Idempotency key
        $idempotencyKey = "pub_{$context->organizationId}_{$post->id}_{$socialAccount->id}_" . time();

        $variation = $post->variations->where('platform', $socialAccount->platform)->first();
        $captionToSend = $variation?->body ?: ($post->hook ? "{$post->hook}\n\n{$post->caption}" : $post->caption);

        $payload = [
            'title' => $post->title,
            'caption' => $captionToSend,
            'cta' => $variation?->cta ?: $post->cta,
            'hashtags' => $variation?->hashtags ?: $post->hashtags,
            'content_type' => $post->content_type,
        ];

        return DB::transaction(function () use ($context, $post, $variation, $socialAccount, $idempotencyKey, $payload) {
            $job = PublishingJobModel::create([
                'organization_id' => $context->organizationId,
                'content_post_id' => $post->id,
                'content_variation_id' => $variation?->id,
                'social_account_id' => $socialAccount->id,
                'idempotency_key' => $idempotencyKey,
                'status' => 'processing',
                'scheduled_at' => Carbon::now(),
                'payload_snapshot' => $payload,
                'attempts' => 1,
            ]);

            try {
                $adapter = $this->publisherFactory->make($socialAccount->platform);
                $result = $adapter->publish($socialAccount, $payload);

                $job->update([
                    'status' => 'published',
                    'published_at' => Carbon::now(),
                    'external_post_id' => $result['external_post_id'],
                    'external_post_url' => $result['external_post_url'],
                ]);

                $post->update([
                    'status' => 'published',
                ]);

                $this->auditService->log(
                    action: 'social.post_published',
                    organizationId: $context->organizationId,
                    userId: $context->userId,
                    entityType: 'publishing_job',
                    entityId: (string) $job->id
                );

                return $job->fresh(['socialAccount', 'post']);
            } catch (\Throwable $e) {
                $job->update([
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Process due scheduled publishing jobs (invoked by cron/queue worker).
     */
    public function processDuePublishingJobs(): int
    {
        $duePosts = ContentPostModel::where('status', 'scheduled')
            ->where('scheduled_at', '<=', Carbon::now())
            ->get();

        $processed = 0;

        foreach ($duePosts as $post) {
            $context = new TenantContext(
                organizationId: $post->organization_id,
                userId: $post->created_by ?: 1,
                role: 'owner',
                permissions: ['social.publish', 'social.view']
            );

            try {
                $this->publishNow($context, $post->id);
                $processed++;
            } catch (\Throwable $e) {
                // Log and continue to next post
                \Illuminate\Support\Facades\Log::error("Failed to publish due post #{$post->id}: " . $e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Get publishing history and jobs.
     */
    public function getPublishingJobs(TenantContext $context, array $filters = []): array
    {
        TenantIsolationGuard::assertPermission($context, 'social.view');

        $query = PublishingJobModel::with(['post', 'socialAccount', 'variation'])
            ->where('organization_id', $context->organizationId)
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $jobs = $query->paginate(20);

        return [
            'data' => $jobs->items(),
            'total' => $jobs->total(),
            'current_page' => $jobs->currentPage(),
            'last_page' => $jobs->lastPage(),
        ];
    }
}
