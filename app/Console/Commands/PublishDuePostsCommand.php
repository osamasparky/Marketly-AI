<?php

namespace App\Console\Commands;

use App\Domains\Publishing\Application\Services\SocialPublishingApplicationService;
use Illuminate\Console\Command;

class PublishDuePostsCommand extends Command
{
    protected $signature = 'marketly:publish-due-posts';

    protected $description = 'Process and publish due scheduled content posts across connected social channels';

    public function handle(SocialPublishingApplicationService $publishingService): int
    {
        $this->info('Checking for due scheduled posts to publish...');

        $processed = $publishingService->processDuePublishingJobs();

        $this->info("Successfully processed and published {$processed} posts.");

        return Command::SUCCESS;
    }
}
