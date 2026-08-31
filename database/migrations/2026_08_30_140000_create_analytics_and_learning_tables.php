<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Post Level Metrics
        Schema::create('post_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('content_post_id')->constrained('content_posts')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->dateTime('captured_at');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('reach')->default(0);
            $table->unsignedBigInteger('likes')->default(0);
            $table->unsignedBigInteger('comments')->default(0);
            $table->unsignedBigInteger('shares')->default(0);
            $table->unsignedBigInteger('saves')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0.00); // e.g. 4.75%
            $table->json('metrics_json')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'captured_at']);
            $table->index(['content_post_id', 'captured_at']);
        });

        // 2. Channel Account Level Snapshots
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->string('platform'); // linkedin, instagram, x, facebook, tiktok
            $table->dateTime('captured_at');
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->bigInteger('followers_delta')->default(0);
            $table->unsignedBigInteger('impressions_count')->default(0);
            $table->unsignedBigInteger('engagements_count')->default(0);
            $table->json('metrics_json')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'platform', 'captured_at']);
        });

        // 3. AI Learning & Actionable Recommendations
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('strategy_id')->nullable()->constrained('marketing_strategies')->nullOnDelete();
            $table->foreignId('pillar_id')->nullable()->constrained('content_pillars')->nullOnDelete();
            $table->string('type'); // winning_hook, optimal_time, pillar_performance, content_fatigue, tone_resonance
            $table->string('title');
            $table->text('explanation');
            $table->json('evidence_json')->nullable();
            $table->json('action_json')->nullable();
            $table->decimal('confidence_score', 4, 2)->default(0.90); // 0.00 to 1.00
            $table->string('status')->default('active'); // active, applied, dismissed
            $table->dateTime('applied_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('analytics_snapshots');
        Schema::dropIfExists('post_metrics');
    }
};
