<?php

namespace App\Providers;

use App\AI\Contracts\AIProviderInterface;
use App\AI\Providers\GeminiAIProvider;
use App\Domains\Brand\Domain\Repositories\BrandAudienceRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandCompetitorRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandGoalRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandProductServiceRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandProfileRepositoryInterface;
use App\Domains\Brand\Domain\Repositories\BrandVoiceRepositoryInterface;
use App\Domains\Brand\Infrastructure\Persistence\Repositories\EloquentBrandAudienceRepository;
use App\Domains\Brand\Infrastructure\Persistence\Repositories\EloquentBrandCompetitorRepository;
use App\Domains\Brand\Infrastructure\Persistence\Repositories\EloquentBrandGoalRepository;
use App\Domains\Brand\Infrastructure\Persistence\Repositories\EloquentBrandProductServiceRepository;
use App\Domains\Brand\Infrastructure\Persistence\Repositories\EloquentBrandProfileRepository;
use App\Domains\Brand\Infrastructure\Persistence\Repositories\EloquentBrandVoiceRepository;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Application\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthorizationService::class);
        $this->app->bind(AIProviderInterface::class, function ($app) {
            $apiKey = (string) config('services.gemini.api_key', '');
            $model = (string) config('services.gemini.model', 'gemini-2.0-flash');

            // If active tenant context has custom BYOK Gemini key, use tenant's key
            try {
                if ($app->bound(\App\Domains\Tenancy\Domain\Entities\TenantContext::class)) {
                    $context = $app->make(\App\Domains\Tenancy\Domain\Entities\TenantContext::class);
                    if ($context && $context->organizationId) {
                        $org = \App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel::find($context->organizationId);
                        if ($org && !empty($org->ai_config_json['gemini_api_key'])) {
                            $apiKey = $org->ai_config_json['gemini_api_key'];
                        }
                    }
                } elseif (request()?->attributes->has('tenant_context')) {
                    $context = request()->attributes->get('tenant_context');
                    if ($context && $context->organizationId) {
                        $org = \App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel::find($context->organizationId);
                        if ($org && !empty($org->ai_config_json['gemini_api_key'])) {
                            $apiKey = $org->ai_config_json['gemini_api_key'];
                        }
                    }
                }
            } catch (\Throwable $e) {}

            return new GeminiAIProvider(
                apiKey: $apiKey,
                model: $model,
                baseUrl: 'https://generativelanguage.googleapis.com/v1beta'
            );
        });

        // Brand Domain Repository Bindings
        $this->app->bind(BrandProfileRepositoryInterface::class, EloquentBrandProfileRepository::class);
        $this->app->bind(BrandProductServiceRepositoryInterface::class, EloquentBrandProductServiceRepository::class);
        $this->app->bind(BrandAudienceRepositoryInterface::class, EloquentBrandAudienceRepository::class);
        $this->app->bind(BrandVoiceRepositoryInterface::class, EloquentBrandVoiceRepository::class);
        $this->app->bind(BrandGoalRepositoryInterface::class, EloquentBrandGoalRepository::class);
        $this->app->bind(BrandCompetitorRepositoryInterface::class, EloquentBrandCompetitorRepository::class);
        $this->app->bind(
            \App\Domains\Brand\Domain\Repositories\BrandAssetRepositoryInterface::class,
            \App\Domains\Brand\Infrastructure\Persistence\Repositories\EloquentBrandAssetRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'user' => UserModel::class,
            'users' => UserModel::class,
            'App\Models\User' => UserModel::class,
        ]);
    }
}
