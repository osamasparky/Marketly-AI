<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Organizations (Tenants)
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20)->default('business')->index(); // business, agency
            $table->string('status', 20)->default('active')->index(); // active, suspended, cancelled
            $table->string('default_locale', 10)->default('en');
            $table->string('timezone', 50)->default('UTC');
            $table->timestamps();
        });

        // 2. Roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // owner, admin, manager, editor, viewer
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // 3. Permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // e.g. content.create, social.publish
            $table->string('module', 50)->index(); // content, social, billing, etc.
            $table->timestamps();
        });

        // 4. Role Permissions Pivot
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // 5. Organization Memberships (Tenant Binding)
        Schema::create('organization_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('status', 20)->default('active')->index(); // active, suspended
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            // Strict unique constraint: One membership per user per organization
            $table->unique(['organization_id', 'user_id'], 'org_user_unique');
        });

        // 6. Organization Invitations
        Schema::create('organization_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('email')->index();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('pending')->index(); // pending, accepted, revoked, expired
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['organization_id', 'email']);
        });

        // 7. Append-Only Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 100)->index();
            $table->string('entity_type', 100)->nullable();
            $table->string('entity_id', 100)->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('organization_invitations');
        Schema::dropIfExists('organization_memberships');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('organizations');
    }
};
