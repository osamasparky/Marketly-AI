<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string'); // string, json, boolean, number
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Insert initial default website settings
        $defaults = [
            [
                'key' => 'hero_title_ar',
                'value' => 'موظفك التسويقي المستقل بالذكاء الاصطناعي',
                'type' => 'string',
                'group' => 'hero',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'hero_title_en',
                'value' => 'Your Autonomous AI Marketing Employee',
                'type' => 'string',
                'group' => 'hero',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'hero_subtitle_ar',
                'value' => 'منظومة سحابية متكاملة لابتكار الاستراتيجيات، صناعة المحتوى، تصميم الإبداعيات، والنشر المباشر على منصات التواصل الاجتماعي.',
                'type' => 'string',
                'group' => 'hero',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'hero_subtitle_en',
                'value' => 'Complete AI-native SaaS that plans, creates, designs, schedules, and publishes high-converting marketing campaigns.',
                'type' => 'string',
                'group' => 'hero',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@marketly.ai',
                'type' => 'string',
                'group' => 'contact',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_phone',
                'value' => '+966 50 000 0000',
                'type' => 'string',
                'group' => 'contact',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'announcement_banner',
                'value' => '🚀 Marketly-AI 2.0 is live with autonomous Gemini generation and LinkedIn direct publishing!',
                'type' => 'string',
                'group' => 'announcement',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'social_links',
                'value' => json_encode([
                    'linkedin' => 'https://linkedin.com/company/marketly-ai',
                    'x' => 'https://x.com/marketly_ai',
                    'facebook' => 'https://facebook.com/marketly.ai',
                ]),
                'type' => 'json',
                'group' => 'social',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('site_settings')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
