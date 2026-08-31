<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_super_admin')) {
                $table->boolean('is_super_admin')->default(false)->after('status')->index();
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'ai_config_json')) {
                $table->text('ai_config_json')->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('organizations', 'website_url')) {
                $table->string('website_url')->nullable()->after('ai_config_json');
            }
            if (!Schema::hasColumn('organizations', 'industry')) {
                $table->string('industry')->nullable()->after('website_url');
            }
            if (!Schema::hasColumn('organizations', 'billing_email')) {
                $table->string('billing_email')->nullable()->after('industry');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_super_admin')) {
                $table->dropColumn('is_super_admin');
            }
        });

        Schema::table('organizations', function (Blueprint $table) {
            $cols = array_filter(['ai_config_json', 'website_url', 'industry', 'billing_email'], fn($c) => Schema::hasColumn('organizations', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
