<?php

namespace App\Domains\Calendar\Application\Services;

use App\Domains\Billing\Domain\Services\EntitlementService;
use App\Domains\Calendar\Domain\Services\CalendarPlannerAgent;
use App\Domains\Content\Domain\Services\PlatformRepurposingService;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentPostModel;
use App\Domains\Content\Infrastructure\Persistence\Models\ContentVariationModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CalendarApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly EntitlementService $entitlementService,
        private readonly CalendarPlannerAgent $plannerAgent,
        private readonly PlatformRepurposingService $repurposingService
    ) {}

    /**
     * Retrieve calendar events within a date range with summary statistics.
     */
    public function getCalendar(TenantContext $context, array $filters = []): array
    {
        TenantIsolationGuard::assertPermission($context, 'calendar.view');

        $startDate = !empty($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : Carbon::now()->startOfMonth()->subDays(7)->startOfDay();

        $endDate = !empty($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : Carbon::now()->addMonth()->endOfMonth()->endOfDay();

        $query = ContentPostModel::with([
            'variations',
            'latestAudit',
            'pillar',
            'campaignTheme',
            'author',
        ])->where('organization_id', $context->organizationId);

        if (!empty($filters['platform'])) {
            $query->where('primary_platform', $filters['platform']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Posts scheduled within range OR created within range (if unscheduled)
        $posts = $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('scheduled_at', [$startDate, $endDate])
              ->orWhere(function ($sub) use ($startDate, $endDate) {
                  $sub->whereNull('scheduled_at')
                      ->whereBetween('created_at', [$startDate, $endDate]);
              });
        })->orderBy('scheduled_at')->get();

        // Calculate metrics
        $metrics = [
            'total_scheduled' => $posts->whereNotNull('scheduled_at')->count(),
            'draft_count' => $posts->where('status', 'draft')->count(),
            'in_review_count' => $posts->where('status', 'in_review')->count(),
            'approved_count' => $posts->where('status', 'approved')->count(),
            'scheduled_count' => $posts->where('status', 'scheduled')->count(),
            'published_count' => $posts->where('status', 'published')->count(),
        ];

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'metrics' => $metrics,
            'posts' => $posts,
        ];
    }

    /**
     * Reschedule post (drag-and-drop or manual time update).
     */
    public function reschedulePost(TenantContext $context, int $postId, string $newScheduledAt): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'calendar.manage');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $scheduledCarbon = Carbon::parse($newScheduledAt);

        $newStatus = in_array($post->status, ['approved', 'scheduled']) ? 'scheduled' : $post->status;

        $post->update([
            'scheduled_at' => $scheduledCarbon->toDateTimeString(),
            'status' => $newStatus,
        ]);

        $this->auditService->log(
            action: 'calendar.rescheduled',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Submit post for editorial review (draft -> in_review).
     */
    public function submitReview(TenantContext $context, int $postId): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.update');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->update(['status' => 'in_review']);

        $this->auditService->log(
            action: 'calendar.submitted_review',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Approve post (in_review/draft -> approved).
     */
    public function approvePost(TenantContext $context, int $postId): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.approve');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->update(['status' => 'approved']);

        $this->auditService->log(
            action: 'calendar.approved',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Schedule post for publication (approved -> scheduled).
     */
    public function schedulePost(TenantContext $context, int $postId, ?string $scheduledAt = null): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'content.approve');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $scheduleTime = $scheduledAt ? Carbon::parse($scheduledAt) : ($post->scheduled_at ?: Carbon::now()->addHour());

        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduleTime->toDateTimeString(),
        ]);

        $this->auditService->log(
            action: 'calendar.scheduled',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Unschedule post back to draft.
     */
    public function unschedulePost(TenantContext $context, int $postId): ContentPostModel
    {
        TenantIsolationGuard::assertPermission($context, 'calendar.manage');

        $post = ContentPostModel::where('organization_id', $context->organizationId)
            ->where('id', $postId)
            ->firstOrFail();

        $post->update([
            'status' => 'draft',
            'scheduled_at' => null,
        ]);

        $this->auditService->log(
            action: 'calendar.unscheduled',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_post',
            entityId: (string) $post->id
        );

        return $post->fresh(['variations', 'latestAudit', 'pillar', 'campaignTheme']);
    }

    /**
     * Generate autonomous 7/14/30-day content calendar plan.
     */
    public function generatePlan(TenantContext $context, array $params): array
    {
        TenantIsolationGuard::assertPermission($context, 'calendar.manage');

        $days = isset($params['horizon_days']) ? (int) $params['horizon_days'] : 7;
        if ($days < 1 || $days > 30) {
            throw new HttpException(422, 'Horizon days must be between 1 and 30.');
        }

        // Quota assertion & consumption (1 credit per planned day)
        $this->entitlementService->assertCanAndConsume($context->organizationId, 'ai_content', min($days, 5));

        $planResult = $this->plannerAgent->plan($context->organizationId, $days, $params);

        $createdPosts = DB::transaction(function () use ($context, $planResult) {
            $saved = [];
            foreach ($planResult['slots'] as $slot) {
                $post = ContentPostModel::create([
                    'organization_id' => $context->organizationId,
                    'strategy_id' => $slot['strategy_id'],
                    'pillar_id' => $slot['pillar_id'],
                    'title' => $slot['title'],
                    'hook' => $slot['hook'],
                    'caption' => $slot['caption'],
                    'cta' => $slot['cta'],
                    'hashtags' => $slot['hashtags'],
                    'primary_platform' => $slot['primary_platform'],
                    'content_type' => $slot['content_type'],
                    'language' => $slot['language'],
                    'dialect' => $slot['dialect'],
                    'tone' => $slot['tone'],
                    'objective' => $slot['objective'],
                    'status' => 'draft',
                    'scheduled_at' => $slot['scheduled_at'],
                    'created_by' => $context->userId,
                ]);

                // Create platform variations
                $variations = $this->repurposingService->repurpose([
                    'title' => $post->title,
                    'hook' => $post->hook,
                    'caption' => $post->caption,
                    'cta' => $post->cta,
                    'hashtags' => $post->hashtags,
                    'language' => $post->language,
                ], ['linkedin', 'instagram', 'x', 'facebook', 'tiktok']);

                foreach ($variations as $platformKey => $varData) {
                    ContentVariationModel::create([
                        'organization_id' => $context->organizationId,
                        'content_post_id' => $post->id,
                        'platform' => $varData['platform'],
                        'format' => $varData['format'],
                        'hook' => $varData['hook'],
                        'body' => $varData['body'],
                        'cta' => $varData['cta'],
                        'hashtags' => $varData['hashtags'],
                        'character_count' => $varData['character_count'],
                        'status' => 'draft',
                    ]);
                }

                $saved[] = $post;
            }

            $this->auditService->log(
                action: 'calendar.plan_generated',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'calendar_plan',
                entityId: (string) count($saved)
            );

            return $saved;
        });

        return [
            'horizon_days' => $days,
            'posts_created' => count($createdPosts),
            'start_date' => $planResult['start_date'],
            'end_date' => $planResult['end_date'],
            'posts' => $createdPosts,
        ];
    }
}
