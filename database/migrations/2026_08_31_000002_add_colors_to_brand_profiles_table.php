<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add brand color palette fields.
     */
    public function up(): void
    {
        Schema::table('brand_profiles', function (Blueprint $table) {
            $table->string('primary_color', 10)->nullable()->default('#10B981')->after('brand_promise');
            $table->string('secondary_color', 10)->nullable()->after('primary_color');
            $table->string('accent_color', 10)->nullable()->after('secondary_color');
            $table->string('background_color', 10)->nullable()->after('accent_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brand_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'primary_color',
                'secondary_color',
                'accent_color',
                'background_color',
            ]);
        });
    }
};
