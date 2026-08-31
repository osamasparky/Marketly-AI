<?php

namespace App\Console\Commands;

use App\Domains\Analytics\Domain\Services\AnalyticsIngestionService;
use App\Domains\Analytics\Domain\Services\LearningFeedbackAgent;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use Illuminate\Console\Command;

class SyncAnalyticsCommand extends Command
{
    protected $signature = 'marketly:sync-analytics';

    protected $description = 'Ingest metrics from social platforms and generate AI learning recommendations for all active organizations';

    public function handle(
        AnalyticsIngestionService $ingestionService,
        LearningFeedbackAgent $feedbackAgent
    ): int {
        $this->info('Starting global analytics synchronization...');

        $organizations = OrganizationModel::where('status', 'active')->get();

        foreach ($organizations as $org) {
            $this->line("Syncing analytics for Organization #{$org->id} ({$org->name})...");
            $ingestionService->syncOrganizationMetrics($org->id);
            $feedbackAgent->generateRecommendations($org->id);
        }

        $this->info("Completed analytics synchronization for {$organizations->count()} organizations.");

        return Command::SUCCESS;
    }
}
