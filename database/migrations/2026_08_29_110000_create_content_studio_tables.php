<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('content_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('brand_profile_id')->nullable()->index();
            $table->unsignedBigInteger('strategy_id')->nullable()->index();
            $table->unsignedBigInteger('pillar_id')->nullable()->index();
            $table->unsignedBigInteger('campaign_theme_id')->nullable()->index();

            $table->string('title');
            $table->text('hook')->nullable();
            $table->longText('caption');
            $table->text('cta')->nullable();
            $table->json('hashtags')->nullable(); // array of strings
            $table->string('primary_platform', 50)->default('linkedin'); // linkedin, instagram, x, facebook, tiktok
            $table->string('content_type', 50)->default('post'); // post, carousel, thread, reel_script, story
            $table->string('language', 10)->default('ar'); // ar, en
            $table->string('dialect', 50)->nullable(); // msa, saudi, egyptian, uae, general
            $table->string('tone', 50)->nullable(); // executive, conversational, witty, educational, direct_response
            $table->string('objective', 50)->default('engagement'); // brand_awareness, lead_generation, sales, education, engagement

            $table->json('visual_brief')->nullable(); // { type, description, suggested_text_overlay, color_notes }
            $table->json('metadata')->nullable();

            $table->enum('status', ['draft', 'in_review', 'approved', 'scheduled', 'published', 'archived'])
                  ->default('draft')
                  ->index();

            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('brand_profile_id')->references('id')->on('brand_profiles')->nullOnDelete();
            $table->foreign('strategy_id')->references('id')->on('marketing_strategies')->nullOnDelete();
            $table->foreign('pillar_id')->references('id')->on('content_pillars')->nullOnDelete();
            $table->foreign('campaign_theme_id')->references('id')->on('campaign_themes')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('content_variations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('content_post_id')->index();

            $table->string('platform', 50); // linkedin, instagram, x, facebook, tiktok
            $table->string('format', 50)->default('post'); // post, thread, carousel, reel_script, story
            $table->text('hook')->nullable();
            $table->longText('body');
            $table->text('cta')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('visual_brief')->nullable();
            $table->json('thread_slides')->nullable(); // For carousels or X threads: [{ order, text, visual }]
            $table->integer('character_count')->default(0);
            $table->enum('status', ['draft', 'ready', 'approved'])->default('draft');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('content_post_id')->references('id')->on('content_posts')->cascadeOnDelete();
            $table->unique(['content_post_id', 'platform']);
        });

        Schema::create('content_quality_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('content_post_id')->index();

            $table->integer('score')->default(0); // 0-100
            $table->integer('brand_alignment_score')->default(0);
            $table->integer('hook_strength_score')->default(0);
            $table->integer('clarity_score')->default(0);
            $table->integer('safety_compliance_score')->default(100);

            $table->json('strengths')->nullable(); // string[]
            $table->json('warnings')->nullable(); // string[]
            $table->json('suggestions')->nullable(); // string[]
            $table->boolean('passed_restrictions')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('content_post_id')->references('id')->on('content_posts')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_quality_audits');
        Schema::dropIfExists('content_variations');
        Schema::dropIfExists('content_posts');
    }
};
