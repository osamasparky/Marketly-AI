<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Connected Social Accounts
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('platform'); // linkedin, instagram, x, facebook, tiktok, youtube
            $table->string('account_name');
            $table->string('account_id'); // External provider platform ID
            $table->string('account_username')->nullable();
            $table->string('account_avatar')->nullable();
            $table->text('access_token'); // Encrypted at application level
            $table->text('refresh_token')->nullable(); // Encrypted at application level
            $table->dateTime('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('health_status')->default('healthy'); // healthy, expired, revoked, error
            $table->dateTime('last_health_check_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'platform', 'account_id']);
            $table->index(['organization_id', 'platform', 'is_active']);
        });

        // 2. Social Publishing Jobs & Audit
        Schema::create('publishing_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('content_post_id')->constrained('content_posts')->cascadeOnDelete();
            $table->foreignId('content_variation_id')->nullable()->constrained('content_variations')->nullOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('idempotency_key')->unique();
            $table->string('status')->default('pending'); // pending, processing, published, failed, cancelled
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('external_post_id')->nullable();
            $table->string('external_post_url')->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->text('last_error')->nullable();
            $table->json('payload_snapshot')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishing_jobs');
        Schema::dropIfExists('social_accounts');
    }
};
