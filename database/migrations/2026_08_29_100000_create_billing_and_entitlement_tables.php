<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Subscription Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0.00);
            $table->decimal('price_annual', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('SAR');
            $table->integer('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Plan Entitlements & Features Table
        Schema::create('plan_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature_key', 50); // brand_brain, ai_strategy, ai_content, team_members, social_accounts, analytics, automation
            $table->boolean('is_enabled')->default(true);
            $table->integer('limit_count')->default(-1); // -1 = unlimited, > 0 = specific limit per month
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
        });

        // 3. Organization Subscriptions Table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('status', 30)->default('trialing'); // trialing, active, past_due, paused, cancelled, expired
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id']);
            $table->index(['organization_id', 'status']);
        });

        // 4. Feature Usage Records Table (scoped to monthly billing period)
        Schema::create('usage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('feature_key', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('used_count')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'feature_key', 'period_start']);
            $table->index(['organization_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_entitlements');
        Schema::dropIfExists('plans');
    }
};
