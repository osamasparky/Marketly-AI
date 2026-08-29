<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Strategy domain normalized entities.
     */
    public function up(): void
    {
        // 1. Marketing Strategies (Root tenant strategy entity)
        Schema::create('marketing_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->nullable()->constrained('brand_profiles')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('primary_objective', 50)->default('lead_generation');
            $table->json('secondary_objectives')->nullable(); // Array of secondary objectives
            $table->string('status', 20)->default('draft'); // 'draft', 'active', 'paused', 'archived'
            $table->unsignedInteger('version')->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('rationale')->nullable(); // AI explanation / strategic logic
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // 2. Content Pillars (Recurring strategic topics & percentage mix)
        Schema::create('content_pillars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('strategy_id')->constrained('marketing_strategies')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('objective', 50)->default('education');
            $table->string('priority', 20)->default('medium'); // 'high', 'medium', 'low'
            $table->unsignedTinyInteger('recommended_percentage')->default(20);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['organization_id', 'strategy_id']);
        });

        // 3. Campaign Themes
        Schema::create('campaign_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('strategy_id')->constrained('marketing_strategies')->cascadeOnDelete();

            $table->string('name');
            $table->string('objective', 50)->nullable();
            $table->string('audience_persona', 100)->nullable();
            $table->text('core_message')->nullable();
            $table->unsignedTinyInteger('duration_weeks')->default(4);
            $table->json('recommended_formats')->nullable(); // Array of formats (e.g. ['carousel', 'reel'])
            $table->string('status', 20)->default('planned');
            $table->timestamps();

            $table->index(['organization_id', 'strategy_id']);
        });

        // 4. Content Opportunities
        Schema::create('content_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('strategy_id')->constrained('marketing_strategies')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('objective', 50)->nullable();
            $table->string('priority', 20)->default('medium'); // 'high', 'medium', 'low'
            $table->string('source', 50)->default('ai_recommended'); // 'ai_recommended', 'seasonal', 'user_defined'
            $table->string('recommended_timing', 100)->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();

            $table->index(['organization_id', 'strategy_id', 'priority']);
        });

        // 5. Strategy Platforms
        Schema::create('strategy_platforms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('strategy_id')->constrained('marketing_strategies')->cascadeOnDelete();

            $table->string('platform', 30); // 'linkedin', 'instagram', 'x', 'tiktok', etc.
            $table->string('primary_objective', 50)->nullable();
            $table->string('posting_frequency', 50)->default('3x_per_week');
            $table->json('recommended_formats')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'strategy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('strategy_platforms');
        Schema::dropIfExists('content_opportunities');
        Schema::dropIfExists('campaign_themes');
        Schema::dropIfExists('content_pillars');
        Schema::dropIfExists('marketing_strategies');
    }
};
