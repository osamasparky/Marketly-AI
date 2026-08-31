<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop any leftover single-brand unique index on usage_records in SQLite / MySQL
        try {
            DB::statement('DROP INDEX IF EXISTS usage_records_organization_id_feature_key_period_start_unique;');
        } catch (\Throwable $e) {}

        // Ensure proper composite unique index for multi-brand quota tracking
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS usage_org_brand_feat_period_unique ON usage_records(organization_id, brand_profile_id, feature_key, period_start);');
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS usage_org_brand_feat_period_unique;');
        } catch (\Throwable $e) {}
    }
};
