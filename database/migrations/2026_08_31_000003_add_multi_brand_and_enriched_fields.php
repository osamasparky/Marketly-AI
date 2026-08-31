<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Multi-Brand support and enriched Brand Brain fields.
     */
    public function up(): void
    {
        // 1. Remove single-brand unique constraint on brand_profiles and add Phase F fields
        Schema::table('brand_profiles', function (Blueprint $table) {
            $table->dropUnique(['organization_id']);
            $table->unique(['organization_id', 'business_name']);

            // Phase F enriched Brand Brain fields
            $table->json('preferred_platforms')->nullable()->after('background_color');
            $table->json('content_pillars_input')->nullable()->after('preferred_platforms');
            $table->json('existing_social_handles')->nullable()->after('content_pillars_input');
            $table->decimal('approximate_monthly_budget', 12, 2)->nullable()->after('existing_social_handles');
            $table->string('budget_currency', 10)->default('SAR')->after('approximate_monthly_budget');
        });

        // 2. Add brand_profile_id to social_accounts
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('organization_id')->constrained('brand_profiles')->nullOnDelete();
            $table->index(['organization_id', 'brand_profile_id']);
        });

        // 3. Add brand_profile_id to media_assets (Creative Studio)
        Schema::table('media_assets', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('organization_id')->constrained('brand_profiles')->nullOnDelete();
            $table->index(['organization_id', 'brand_profile_id']);
        });

        // 4. Add brand_profile_id to Analytics tables
        Schema::table('post_metrics', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('organization_id')->constrained('brand_profiles')->nullOnDelete();
            $table->index(['organization_id', 'brand_profile_id']);
        });

        Schema::table('analytics_snapshots', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('organization_id')->constrained('brand_profiles')->nullOnDelete();
            $table->index(['organization_id', 'brand_profile_id']);
        });

        Schema::table('ai_recommendations', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('organization_id')->constrained('brand_profiles')->nullOnDelete();
            $table->index(['organization_id', 'brand_profile_id']);
        });

        // 5. Add brand_profile_id to usage_records (Per-Brand AI quotas)
        Schema::table('usage_records', function (Blueprint $table) {
            $table->foreignId('brand_profile_id')->nullable()->after('organization_id')->constrained('brand_profiles')->nullOnDelete();
            $table->dropUnique(['organization_id', 'feature_key', 'period_start']);
            $table->unique(['organization_id', 'brand_profile_id', 'feature_key', 'period_start'], 'usage_org_brand_feat_period_unique');
            $table->index(['organization_id', 'brand_profile_id', 'feature_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });

        Schema::table('ai_recommendations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });

        Schema::table('analytics_snapshots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });

        Schema::table('post_metrics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });

        Schema::table('media_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });

        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_profile_id');
        });

        Schema::table('brand_profiles', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'business_name']);
            $table->unique('organization_id');
            $table->dropColumn([
                'preferred_platforms',
                'content_pillars_input',
                'existing_social_handles',
                'approximate_monthly_budget',
                'budget_currency',
            ]);
        });
    }
};
