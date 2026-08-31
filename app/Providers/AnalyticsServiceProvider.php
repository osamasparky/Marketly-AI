<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Analytics\Application\Services\AnalyticsApplicationService;
use App\Domains\Analytics\Domain\Services\AnalyticsIngestionService;
use App\Domains\Analytics\Domain\Services\PerformanceAttributionAgent;
use App\Domains\Analytics\Domain\Services\LearningFeedbackAgent;

class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AnalyticsApplicationService::class, function ($app) {
            return new AnalyticsApplicationService(
                $app->make(\App\Domains\Tenancy\Application\Services\AuditApplicationService::class),
                $app->make(AnalyticsIngestionService::class),
                $app->make(PerformanceAttributionAgent::class),
                $app->make(LearningFeedbackAgent::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // No boot actions required for now.
    }
}
