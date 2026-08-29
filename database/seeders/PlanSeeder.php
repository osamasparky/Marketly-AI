<?php

namespace Database\Seeders;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Starter Tier (Free Trial / Solopreneurs)
        $starter = PlanModel::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Perfect for early-stage startups and solopreneurs exploring AI marketing autonomy.',
                'price_monthly' => 0.00,
                'price_annual' => 0.00,
                'currency' => 'SAR',
                'trial_days' => 14,
                'is_active' => true,
            ]
        );

        $starter->entitlements()->updateOrCreate(['feature_key' => 'brand_brain'], ['is_enabled' => true, 'limit_count' => -1]);
        $starter->entitlements()->updateOrCreate(['feature_key' => 'ai_strategy'], ['is_enabled' => true, 'limit_count' => 5]); // 5 strategies/month
        $starter->entitlements()->updateOrCreate(['feature_key' => 'ai_content'], ['is_enabled' => true, 'limit_count' => 30]); // 30 posts/month
        $starter->entitlements()->updateOrCreate(['feature_key' => 'team_members'], ['is_enabled' => true, 'limit_count' => 2]); // 2 team members
        $starter->entitlements()->updateOrCreate(['feature_key' => 'social_accounts'], ['is_enabled' => false, 'limit_count' => 0]);
        $starter->entitlements()->updateOrCreate(['feature_key' => 'analytics'], ['is_enabled' => false, 'limit_count' => 0]);
        $starter->entitlements()->updateOrCreate(['feature_key' => 'automation'], ['is_enabled' => false, 'limit_count' => 0]);

        // 2. Growth Tier (Scale-ups & Growing Brands)
        $growth = PlanModel::updateOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth',
                'description' => 'For growing teams requiring continuous strategic generation and multi-channel publishing.',
                'price_monthly' => 299.00,
                'price_annual' => 2870.00, // 20% annual discount
                'currency' => 'SAR',
                'trial_days' => 14,
                'is_active' => true,
            ]
        );

        $growth->entitlements()->updateOrCreate(['feature_key' => 'brand_brain'], ['is_enabled' => true, 'limit_count' => -1]);
        $growth->entitlements()->updateOrCreate(['feature_key' => 'ai_strategy'], ['is_enabled' => true, 'limit_count' => 20]);
        $growth->entitlements()->updateOrCreate(['feature_key' => 'ai_content'], ['is_enabled' => true, 'limit_count' => 150]);
        $growth->entitlements()->updateOrCreate(['feature_key' => 'team_members'], ['is_enabled' => true, 'limit_count' => 5]);
        $growth->entitlements()->updateOrCreate(['feature_key' => 'social_accounts'], ['is_enabled' => true, 'limit_count' => 5]);
        $growth->entitlements()->updateOrCreate(['feature_key' => 'analytics'], ['is_enabled' => true, 'limit_count' => -1]);
        $growth->entitlements()->updateOrCreate(['feature_key' => 'automation'], ['is_enabled' => false, 'limit_count' => 0]);

        // 3. Pro Tier (Agencies & Enterprises)
        $pro = PlanModel::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Unlimited autonomous marketing engine with dedicated enterprise agency features.',
                'price_monthly' => 699.00,
                'price_annual' => 6710.00,
                'currency' => 'SAR',
                'trial_days' => 14,
                'is_active' => true,
            ]
        );

        $pro->entitlements()->updateOrCreate(['feature_key' => 'brand_brain'], ['is_enabled' => true, 'limit_count' => -1]);
        $pro->entitlements()->updateOrCreate(['feature_key' => 'ai_strategy'], ['is_enabled' => true, 'limit_count' => -1]); // Unlimited
        $pro->entitlements()->updateOrCreate(['feature_key' => 'ai_content'], ['is_enabled' => true, 'limit_count' => -1]); // Unlimited
        $pro->entitlements()->updateOrCreate(['feature_key' => 'team_members'], ['is_enabled' => true, 'limit_count' => -1]);
        $pro->entitlements()->updateOrCreate(['feature_key' => 'social_accounts'], ['is_enabled' => true, 'limit_count' => -1]);
        $pro->entitlements()->updateOrCreate(['feature_key' => 'analytics'], ['is_enabled' => true, 'limit_count' => -1]);
        $pro->entitlements()->updateOrCreate(['feature_key' => 'automation'], ['is_enabled' => true, 'limit_count' => -1]);
    }
}
