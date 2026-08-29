<?php

namespace App\Domains\Shared\Enums;

enum UserRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    /**
     * Granular permission matrix enforcing the principle of least privilege (Section 36).
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::OWNER => [
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
            self::ADMIN => [
                'organization.view', 'organization.manage',
                'members.view', 'members.invite', 'members.update', 'members.remove',
                'brand.view', 'brand.create', 'brand.update', 'brand.delete',
                'content.view', 'content.create', 'content.update', 'content.delete', 'content.approve',
                'campaign.view', 'campaign.create', 'campaign.update', 'campaign.delete',
                'social.view', 'social.connect', 'social.disconnect', 'social.publish',
                'analytics.view',
                'billing.view',
            ],
            self::MANAGER => [
                'organization.view',
                'members.view', 'members.invite',
                'brand.view', 'brand.update',
                'content.view', 'content.create', 'content.update', 'content.approve',
                'campaign.view', 'campaign.create', 'campaign.update',
                'social.view', 'social.publish',
                'analytics.view',
            ],
            self::EDITOR => [
                'organization.view',
                'brand.view',
                'content.view', 'content.create', 'content.update',
                'campaign.view',
                'social.view',
                'analytics.view',
            ],
            self::VIEWER => [
                'organization.view',
                'brand.view',
                'content.view',
                'campaign.view',
                'social.view',
                'analytics.view',
            ],
        };
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function canApproveContent(): bool
    {
        return $this->hasPermission('content.approve');
    }

    public function canPublish(): bool
    {
        return $this->hasPermission('social.publish');
    }

    public function canManageSocialAccounts(): bool
    {
        return $this->hasPermission('social.connect');
    }
}
