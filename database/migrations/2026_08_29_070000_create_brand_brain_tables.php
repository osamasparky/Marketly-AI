<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Brand Brain normalized entities.
     */
    public function up(): void
    {
        // 1. Business & Brand Profiles (Root tenant Brand Profile)
        Schema::create('brand_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            
            // Business Details
            $table->string('business_name');
            $table->string('legal_name')->nullable();
            $table->string('industry', 50)->default('Technology');
            $table->string('business_type', 30)->default('B2B');
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('country', 10)->default('SA');
            $table->string('region', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->string('default_locale', 10)->default('ar');

            // Brand Identity
            $table->string('tagline')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->json('values')->nullable(); // Array of core values
            $table->text('positioning')->nullable();
            $table->json('unique_selling_points')->nullable(); // Array of USPs
            $table->text('brand_promise')->nullable();

            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique('organization_id');
            $table->index(['organization_id', 'status']);
        });

        // 2. Products & Services
        Schema::create('brand_products_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();

            $table->string('name');
            $table->string('type', 20)->default('product'); // 'product' or 'service'
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 10)->default('SAR');
            $table->string('url')->nullable();
            $table->json('features')->nullable(); // Feature bullet points
            $table->json('target_audience_ids')->nullable(); // Link to target audience IDs
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['brand_profile_id', 'type']);
        });

        // 3. Target Audiences (B2C & B2B)
        Schema::create('brand_audiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();

            $table->string('name');
            $table->string('type', 20)->default('b2c'); // 'b2c' or 'b2b'
            $table->text('description')->nullable();
            
            // B2C Dimensions
            $table->string('age_range', 30)->nullable();
            $table->string('gender', 20)->default('all');
            $table->json('locations')->nullable();
            $table->json('interests')->nullable();
            $table->json('pain_points')->nullable();
            $table->json('needs')->nullable();
            $table->string('buying_behavior', 100)->nullable();
            $table->string('income_level', 50)->nullable();

            // B2B Dimensions
            $table->string('industry', 100)->nullable();
            $table->string('company_size', 50)->nullable();
            $table->json('job_titles')->nullable();
            $table->json('decision_makers')->nullable();
            $table->json('business_challenges')->nullable();

            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // 4. Brand Voice & Tone
        Schema::create('brand_voices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();

            // Tones & Scales
            $table->json('primary_tones')->nullable(); // e.g. ['professional', 'friendly']
            $table->unsignedTinyInteger('formality_scale')->default(3); // 1 (Casual) to 5 (Formal)
            $table->unsignedTinyInteger('playfulness_scale')->default(2); // 1 (Serious) to 5 (Playful)
            $table->unsignedTinyInteger('boldness_scale')->default(3); // 1 (Conservative) to 5 (Bold)
            $table->unsignedTinyInteger('simplicity_scale')->default(4); // 1 (Technical) to 5 (Simple)

            // Writing Rules & Constraints
            $table->json('preferred_phrases')->nullable();
            $table->json('forbidden_phrases')->nullable();
            $table->json('words_to_avoid')->nullable();
            $table->json('words_to_emphasize')->nullable();
            $table->json('cta_preferences')->nullable();
            $table->string('emoji_style', 20)->default('moderate'); // 'none', 'minimal', 'moderate', 'expressive'
            $table->string('hashtag_style', 30)->default('targeted');
            $table->string('dialect', 50)->default('saudi'); // 'saudi', 'egyptian', 'gulf', 'msa', etc.
            $table->json('language_specific_notes')->nullable();

            $table->timestamps();

            $table->unique('organization_id');
            $table->unique('brand_profile_id');
        });

        // 5. Business Goals & Priorities
        Schema::create('brand_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();

            $table->string('goal_type', 50); // e.g. 'lead_generation', 'brand_awareness', 'sales'
            $table->string('priority', 20)->default('primary'); // 'primary', 'secondary', 'tertiary'
            $table->text('description')->nullable();
            $table->json('target_metrics')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // 6. Competitors
        Schema::create('brand_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();

            $table->string('name');
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('positioning')->nullable();
            $table->json('strengths')->nullable();
            $table->json('weaknesses')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id']);
        });

        // 7. Locations & Branches
        Schema::create('brand_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();

            $table->string('name');
            $table->string('country', 10)->default('SA');
            $table->string('region', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // 8. Brand Assets (Private by default)
        Schema::create('brand_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('brand_profile_id')->constrained('brand_profiles')->cascadeOnDelete();

            $table->string('name');
            $table->string('type', 30)->default('logo'); // 'logo', 'guideline_doc', 'palette', 'typography'
            $table->string('file_path');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_public')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_assets');
        Schema::dropIfExists('brand_locations');
        Schema::dropIfExists('brand_competitors');
        Schema::dropIfExists('brand_goals');
        Schema::dropIfExists('brand_voices');
        Schema::dropIfExists('brand_audiences');
        Schema::dropIfExists('brand_products_services');
        Schema::dropIfExists('brand_profiles');
    }
};
