<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissionsByModule = [
            'organization' => [
                'organization.view' => 'View organization profile and details',
                'organization.manage' => 'Update organization settings and configuration',
            ],
            'members' => [
                'members.view' => 'View organization members and roles',
                'members.invite' => 'Invite new members to the organization',
                'members.update' => 'Update member roles and status',
                'members.remove' => 'Remove members from the organization',
            ],
            'brand' => [
                'brand.view' => 'View brand profiles and knowledge assets',
                'brand.create' => 'Create new brand profiles',
                'brand.update' => 'Update brand profiles and Brand Brain',
                'brand.delete' => 'Delete brand profiles',
            ],
            'content' => [
                'content.view' => 'View created posts and drafts',
                'content.create' => 'Create and generate new content',
                'content.update' => 'Edit content captions, hooks, and CTAs',
                'content.delete' => 'Delete content drafts',
                'content.approve' => 'Approve content for scheduled publishing',
            ],
            'campaign' => [
                'campaign.view' => 'View marketing campaigns and pillars',
                'campaign.create' => 'Create marketing campaigns and goals',
                'campaign.update' => 'Update marketing campaigns and strategy',
                'campaign.delete' => 'Delete marketing campaigns',
            ],
            'social' => [
                'social.view' => 'View connected social accounts and channels',
                'social.connect' => 'Connect and authorize social accounts (OAuth)',
                'social.disconnect' => 'Disconnect and revoke social accounts',
                'social.publish' => 'Publish and schedule posts to social networks',
            ],
            'analytics' => [
                'analytics.view' => 'View performance metrics and AI insights',
            ],
            'billing' => [
                'billing.view' => 'View subscription tier, quotas, and invoices',
                'billing.manage' => 'Manage subscription plans and payment methods',
            ],
            'administration' => [
                'administration.manage' => 'Platform administration oversight',
            ],
        ];

        // 1. Insert permissions
        $permissionIds = [];
        foreach ($permissionsByModule as $module => $perms) {
            foreach ($perms as $slug => $desc) {
                $id = DB::table('permissions')->insertGetId([
                    'name' => ucwords(str_replace(['.', '_'], ' ', $slug)),
                    'slug' => $slug,
                    'module' => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $permissionIds[$slug] = $id;
            }
        }

        // 2. Define baseline database role permissions
        $roleDefinitions = [
            'owner' => [
                'name' => 'Owner',
                'description' => 'Full administrative control, billing ownership, and organization management',
                'permissions' => [
                    'organization.view', 'organization.manage',
                    'members.view', 'members.invite', 'members.update', 'members.remove',
                    'brand.view', 'brand.create', 'brand.update', 'brand.delete',
                    'content.view', 'content.create', 'content.update', 'content.delete', 'content.approve',
                    'campaign.view', 'campaign.create', 'campaign.update', 'campaign.delete',
                    'social.view', 'social.connect', 'social.disconnect', 'social.publish',
                    'analytics.view',
                    'billing.view', 'billing.manage',
                    'administration.manage',
                ],
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Organization operations, member invitations, brand and publishing management',
                'permissions' => [
                    'organization.view', 'organization.manage',
                    'members.view', 'members.invite', 'members.update', 'members.remove',
                    'brand.view', 'brand.create', 'brand.update', 'brand.delete',
                    'content.view', 'content.create', 'content.update', 'content.delete', 'content.approve',
                    'campaign.view', 'campaign.create', 'campaign.update', 'campaign.delete',
                    'social.view', 'social.connect', 'social.disconnect', 'social.publish',
                    'analytics.view',
                    'billing.view',
                ],
            ],
            'manager' => [
                'name' => 'Marketing Manager',
                'description' => 'Brand strategy, campaign planning, content approval, and publishing execution',
                'permissions' => [
                    'organization.view',
                    'members.view', 'members.invite',
                    'brand.view', 'brand.update',
                    'content.view', 'content.create', 'content.update', 'content.approve',
                    'campaign.view', 'campaign.create', 'campaign.update',
                    'social.view', 'social.publish',
                    'analytics.view',
                ],
            ],
            'editor' => [
                'name' => 'Content Editor',
                'description' => 'Content generation and drafting without publishing or billing access',
                'permissions' => [
                    'organization.view',
                    'brand.view',
                    'content.view', 'content.create', 'content.update',
                    'campaign.view',
                    'social.view',
                    'analytics.view',
                ],
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Read-only access to campaigns, content, and performance analytics',
                'permissions' => [
                    'organization.view',
                    'brand.view',
                    'content.view',
                    'campaign.view',
                    'social.view',
                    'analytics.view',
                ],
            ],
        ];

        foreach ($roleDefinitions as $roleSlug => $roleData) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => $roleData['name'],
                'slug' => $roleSlug,
                'description' => $roleData['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($roleData['permissions'] as $pSlug) {
                if (isset($permissionIds[$pSlug])) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionIds[$pSlug],
                    ]);
                }
            }
        }
    }
}
