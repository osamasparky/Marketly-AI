<?php

namespace Database\Seeders;

use App\Domains\Billing\Infrastructure\Persistence\Models\PlanModel;
use App\Domains\Billing\Infrastructure\Persistence\Models\SubscriptionModel;
use App\Domains\Identity\Infrastructure\Persistence\Models\UserModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationMembershipModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\OrganizationModel;
use App\Domains\Tenancy\Infrastructure\Persistence\Models\RoleModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed default Super Admin user and primary admin workspace.
     */
    public function run(): void
    {
        // 1. Create or Update Super Admin User
        $superAdmin = UserModel::updateOrCreate(
            ['email' => 'admin@marketly.ai'],
            [
                'name' => 'Marketly Super Admin',
                'password' => Hash::make('Password123!'),
                'is_super_admin' => true,
                'status' => 'active',
                'locale' => 'en',
                'timezone' => 'Asia/Riyadh',
            ]
        );

        // 2. Create Marketly HQ Master Organization
        $org = OrganizationModel::firstOrCreate(
            ['slug' => 'marketly-hq'],
            [
                'name' => 'Marketly HQ',
                'type' => 'agency',
                'status' => 'active',
                'default_locale' => 'en',
                'timezone' => 'Asia/Riyadh',
                'website_url' => 'https://marketly.ai',
                'industry' => 'Marketing Technology',
                'billing_email' => 'admin@marketly.ai',
            ]
        );

        $superAdmin->update(['current_organization_id' => $org->id]);

        $ownerRole = RoleModel::where('slug', 'owner')->first();
        if ($ownerRole) {
            OrganizationMembershipModel::firstOrCreate(
                [
                    'organization_id' => $org->id,
                    'user_id' => $superAdmin->id,
                ],
                [
                    'role_id' => $ownerRole->id,
                    'status' => 'active',
                    'joined_at' => Carbon::now(),
                ]
            );
        }

        // 3. Ensure Enterprise subscription exists for Marketly HQ
        $enterprisePlan = PlanModel::where('slug', 'enterprise')->first();
        if ($enterprisePlan) {
            SubscriptionModel::updateOrCreate(
                ['organization_id' => $org->id],
                [
                    'plan_id' => $enterprisePlan->id,
                    'status' => 'active',
                    'current_period_start' => Carbon::now(),
                    'current_period_end' => Carbon::now()->addYear(),
                    'cancel_at_period_end' => false,
                ]
            );
        }
    }
}
