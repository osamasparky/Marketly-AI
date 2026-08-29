<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('email');
            $table->string('timezone', 50)->default('UTC')->after('locale');
            $table->string('status', 20)->default('active')->after('timezone')->index();
            $table->unsignedBigInteger('current_organization_id')->nullable()->after('status')->index();
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'timezone', 'status', 'current_organization_id', 'last_login_at']);
        });
    }
};
