<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy organization-level unique index on brand_voices so multiple brands can have their own voice
        try {
            DB::statement('DROP INDEX IF EXISTS brand_voices_organization_id_unique;');
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS brand_voices_organization_id_unique ON brand_voices(organization_id);');
        } catch (\Throwable $e) {}
    }
};
