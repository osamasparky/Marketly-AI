<?php

namespace Database\Seeders;

use App\Domains\Shared\Enums\UserRole;
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

        // 2. Insert roles and map permissions
        $roleDefinitions = [
            'owner' => [
                'name' => 'Owner',
                'description' => 'Full administrative control, billing ownership, and organization management',
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Organization operations, member invitations, brand and publishing management',
            ],
            'manager' => [
                'name' => 'Marketing Manager',
                'description' => 'Brand strategy, campaign planning, content approval, and publishing execution',
            ],
            'editor' => [
                'name' => 'Content Editor',
                'description' => 'Content generation and drafting without publishing or billing access',
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Read-only access to campaigns, content, and performance analytics',
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

            $enumRole = UserRole::from($roleSlug);
            $rolePermSlugs = $enumRole->permissions();

            foreach ($rolePermSlugs as $pSlug) {
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
