<?php

namespace App\Domains\Strategy\Application\Services;

use App\Domains\Brand\Infrastructure\Persistence\Models\BrandProfileModel;
use App\Domains\Strategy\Domain\Services\StrategyHealthCalculator;
use App\Domains\Strategy\Infrastructure\Persistence\Models\CampaignThemeModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentOpportunityModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\ContentPillarModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\MarketingStrategyModel;
use App\Domains\Strategy\Infrastructure\Persistence\Models\StrategyPlatformModel;
use App\Domains\Tenancy\Application\Services\AuditApplicationService;
use App\Domains\Tenancy\Domain\Entities\TenantContext;
use App\Domains\Tenancy\Infrastructure\Services\TenantIsolationGuard;
use App\Domains\Billing\Domain\Services\EntitlementService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StrategyApplicationService
{
    public function __construct(
        private readonly AuditApplicationService $auditService,
        private readonly StrategyContextBuilder $contextBuilder,
        private readonly MarketingStrategyGenerator $strategyGenerator,
        private readonly StrategyHealthCalculator $healthCalculator,
        private readonly EntitlementService $entitlementService
    ) {}

    /**
     * Get active or latest marketing strategy with health evaluation.
     */
    public function getActiveOrLatestStrategy(TenantContext $context): array
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.view');

        $query = MarketingStrategyModel::with([
            'pillars',
            'campaignThemes',
            'opportunities',
            'platforms',
        ])->where('organization_id', $context->organizationId);

        if ($context->brandId) {
            $query->where(function ($q) use ($context) {
                $q->where('brand_profile_id', $context->brandId)
                  ->orWhereNull('brand_profile_id');
            });
        }

        $strategy = $query->orderByRaw("CASE WHEN status = 'active' THEN 1 WHEN status = 'draft' THEN 2 ELSE 3 END")
          ->latest()
          ->first();

        $health = $this->healthCalculator->calculate($strategy);

        return [
            'strategy' => $strategy,
            'health' => $health,
        ];
    }

    /**
     * Get a specific strategy by ID.
     */
    public function getStrategy(TenantContext $context, int $strategyId): MarketingStrategyModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.view');

        $query = MarketingStrategyModel::with([
            'pillars',
            'campaignThemes',
            'opportunities',
            'platforms',
        ])->where('organization_id', $context->organizationId)
          ->where('id', $strategyId);

        if ($context->brandId) {
            $query->where(function ($q) use ($context) {
                $q->where('brand_profile_id', $context->brandId)
                  ->orWhereNull('brand_profile_id');
            });
        }

        return $query->firstOrFail();
    }

    /**
     * Generate structured AI marketing strategy draft from Brand Brain context.
     */
    public function generateStrategy(TenantContext $context, array $params): MarketingStrategyModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.generate');

        // Verify and consume subscription plan quota per brand
        $this->entitlementService->assertCanAndConsume($context->organizationId, 'ai_strategy', 1, $context->brandId);

        $profile = $context->brandId 
            ? BrandProfileModel::where('id', $context->brandId)->where('organization_id', $context->organizationId)->first()
            : BrandProfileModel::where('organization_id', $context->organizationId)->first();

        // 1. Build bounded strategic context
        $strategyContext = $this->contextBuilder->build(
            organizationId: $context->organizationId,
            primaryObjective: $params['primary_objective'] ?? 'lead_generation',
            targetPlatforms: $params['target_platforms'] ?? ['linkedin', 'instagram'],
            timeHorizonMonths: (int) ($params['time_horizon_months'] ?? 3),
            seasonalFocus: $params['seasonal_focus'] ?? null,
            targetAudienceId: isset($params['target_audience_id']) ? (int) $params['target_audience_id'] : null,
            brandProfileId: $context->brandId ?? $profile?->id
        );

        // 2. Generate and validate AI strategy draft
        $generatedData = $this->strategyGenerator->generate($strategyContext);

        // 3. Persist strategy in database transaction
        return DB::transaction(function () use ($context, $params, $generatedData, $profile) {
            $strategy = MarketingStrategyModel::create([
                'organization_id' => $context->organizationId,
                'brand_profile_id' => $context->brandId ?? $profile?->id,
                'created_by' => $context->userId,
                'name' => $generatedData['title'] ?? 'AI Marketing Strategy Draft',
                'primary_objective' => $params['primary_objective'] ?? 'lead_generation',
                'description' => $generatedData['summary'] ?? null,
                'status' => 'draft',
                'version' => 1,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths((int) ($params['time_horizon_months'] ?? 3))->toDateString(),
                'rationale' => $generatedData['rationale'],
            ]);

            // Save Content Pillars
            foreach ($generatedData['pillars'] as $pillar) {
                $strategy->pillars()->create([
                    'organization_id' => $context->organizationId,
                    'name' => $pillar['name'],
                    'description' => $pillar['description'],
                    'objective' => $pillar['objective'],
                    'priority' => $pillar['priority'],
                    'recommended_percentage' => $pillar['recommended_percentage'],
                    'status' => 'active',
                ]);
            }

            // Save Campaign Themes
            foreach ($generatedData['campaign_themes'] as $theme) {
                $strategy->campaignThemes()->create([
                    'organization_id' => $context->organizationId,
                    'name' => $theme['name'],
                    'objective' => $theme['objective'],
                    'audience_persona' => $theme['audience_persona'],
                    'core_message' => $theme['core_message'],
                    'duration_weeks' => $theme['duration_weeks'],
                    'recommended_formats' => $theme['recommended_formats'],
                    'status' => 'planned',
                ]);
            }

            // Save Opportunities
            foreach ($generatedData['opportunities'] as $opp) {
                $strategy->opportunities()->create([
                    'organization_id' => $context->organizationId,
                    'title' => $opp['title'],
                    'description' => $opp['description'],
                    'objective' => $opp['objective'],
                    'priority' => $opp['priority'],
                    'source' => $opp['source'],
                    'recommended_timing' => $opp['recommended_timing'],
                    'status' => 'open',
                ]);
            }

            // Save Platforms
            foreach ($generatedData['platforms'] as $plat) {
                $strategy->platforms()->create([
                    'organization_id' => $context->organizationId,
                    'platform' => $plat['platform'],
                    'primary_objective' => $plat['primary_objective'],
                    'posting_frequency' => $plat['posting_frequency'],
                    'recommended_formats' => $plat['recommended_formats'],
                ]);
            }

            $this->auditService->log(
                action: 'strategy.generated',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'marketing_strategy',
                entityId: (string) $strategy->id
            );

            return $strategy->load(['pillars', 'campaignThemes', 'opportunities', 'platforms']);
        });
    }

    /**
     * Atomically activate a marketing strategy and pause any currently active strategy.
     */
    public function activateStrategy(TenantContext $context, int $strategyId): MarketingStrategyModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.activate');

        return DB::transaction(function () use ($context, $strategyId) {
            $strategy = MarketingStrategyModel::where('organization_id', $context->organizationId)
                ->where('id', $strategyId)
                ->firstOrFail();

            // Pause all currently active strategies for this tenant
            MarketingStrategyModel::where('organization_id', $context->organizationId)
                ->where('id', '!=', $strategyId)
                ->where('status', 'active')
                ->update(['status' => 'paused']);

            // Activate target strategy
            $strategy->update([
                'status' => 'active',
                'version' => $strategy->version + 1,
            ]);

            $this->auditService->log(
                action: 'strategy.activated',
                organizationId: $context->organizationId,
                userId: $context->userId,
                entityType: 'marketing_strategy',
                entityId: (string) $strategy->id
            );

            return $strategy->fresh(['pillars', 'campaignThemes', 'opportunities', 'platforms']);
        });
    }

    /**
     * Pause an active marketing strategy.
     */
    public function pauseStrategy(TenantContext $context, int $strategyId): MarketingStrategyModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $strategy = MarketingStrategyModel::where('organization_id', $context->organizationId)
            ->where('id', $strategyId)
            ->firstOrFail();

        $strategy->update(['status' => 'paused']);

        $this->auditService->log(
            action: 'strategy.paused',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'marketing_strategy',
            entityId: (string) $strategy->id
        );

        return $strategy;
    }

    /**
     * Archive a marketing strategy.
     */
    public function archiveStrategy(TenantContext $context, int $strategyId): MarketingStrategyModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $strategy = MarketingStrategyModel::where('organization_id', $context->organizationId)
            ->where('id', $strategyId)
            ->firstOrFail();

        $strategy->update(['status' => 'archived']);

        $this->auditService->log(
            action: 'strategy.archived',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'marketing_strategy',
            entityId: (string) $strategy->id
        );

        return $strategy;
    }

    /**
     * Delete a draft strategy.
     */
    public function deleteStrategy(TenantContext $context, int $strategyId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.delete');

        $strategy = MarketingStrategyModel::where('organization_id', $context->organizationId)
            ->where('id', $strategyId)
            ->firstOrFail();

        $strategy->delete();

        $this->auditService->log(
            action: 'strategy.deleted',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'marketing_strategy',
            entityId: (string) $strategyId
        );

        return true;
    }

    /**
     * Update strategy parameters.
     */
    public function updateStrategy(TenantContext $context, int $strategyId, array $data): MarketingStrategyModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $strategy = MarketingStrategyModel::where('organization_id', $context->organizationId)
            ->where('id', $strategyId)
            ->firstOrFail();

        $strategy->update(array_merge($data, [
            'version' => $strategy->version + 1,
        ]));

        $this->auditService->log(
            action: 'strategy.updated',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'marketing_strategy',
            entityId: (string) $strategy->id
        );

        return $strategy->fresh(['pillars', 'campaignThemes', 'opportunities', 'platforms']);
    }

    /**
     * Get strategy health breakdown.
     */
    public function getStrategyHealth(TenantContext $context, int $strategyId): array
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.view');

        $strategy = $this->getStrategy($context, $strategyId);

        return $this->healthCalculator->calculate($strategy);
    }

    /**
     * Save or update Content Pillar.
     */
    public function savePillar(TenantContext $context, int $strategyId, array $data, ?int $pillarId = null): ContentPillarModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $strategy = MarketingStrategyModel::where('organization_id', $context->organizationId)
            ->where('id', $strategyId)
            ->firstOrFail();

        if ($pillarId) {
            $pillar = ContentPillarModel::where('organization_id', $context->organizationId)
                ->where('strategy_id', $strategyId)
                ->where('id', $pillarId)
                ->firstOrFail();
            $pillar->update($data);
        } else {
            $data['organization_id'] = $context->organizationId;
            $data['strategy_id'] = $strategy->id;
            $pillar = ContentPillarModel::create($data);
        }

        $strategy->increment('version');

        $this->auditService->log(
            action: $pillarId ? 'strategy.pillar.updated' : 'strategy.pillar.created',
            organizationId: $context->organizationId,
            userId: $context->userId,
            entityType: 'content_pillar',
            entityId: (string) $pillar->id
        );

        return $pillar;
    }

    /**
     * Delete Content Pillar.
     */
    public function deletePillar(TenantContext $context, int $strategyId, int $pillarId): bool
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $pillar = ContentPillarModel::where('organization_id', $context->organizationId)
            ->where('strategy_id', $strategyId)
            ->where('id', $pillarId)
            ->firstOrFail();

        $pillar->delete();

        return true;
    }

    /**
     * Get Campaign Themes for a strategy.
     */
    public function getCampaignThemes(TenantContext $context, int $strategyId): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.view');

        $strategy = $this->getStrategy($context, $strategyId);

        return $strategy->campaignThemes;
    }

    /**
     * Save Campaign Theme.
     */
    public function saveCampaignTheme(TenantContext $context, int $strategyId, array $data): CampaignThemeModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $strategy = $this->getStrategy($context, $strategyId);
        $data['organization_id'] = $context->organizationId;
        $data['strategy_id'] = $strategy->id;

        return CampaignThemeModel::create($data);
    }

    /**
     * Get Opportunities for a strategy.
     */
    public function getOpportunities(TenantContext $context, int $strategyId): Collection
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.view');

        $strategy = $this->getStrategy($context, $strategyId);

        return $strategy->opportunities;
    }

    /**
     * Save Opportunity.
     */
    public function saveOpportunity(TenantContext $context, int $strategyId, array $data): ContentOpportunityModel
    {
        TenantIsolationGuard::assertPermission($context, 'strategy.update');

        $strategy = $this->getStrategy($context, $strategyId);
        $data['organization_id'] = $context->organizationId;
        $data['strategy_id'] = $strategy->id;

        return ContentOpportunityModel::create($data);
    }
}
