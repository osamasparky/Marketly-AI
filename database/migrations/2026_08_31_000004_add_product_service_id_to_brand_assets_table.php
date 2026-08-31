<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Phase J: Product/Service image assets.
     */
    public function up(): void
    {
        Schema::table('brand_assets', function (Blueprint $table) {
            $table->foreignId('product_service_id')
                ->nullable()
                ->after('brand_profile_id')
                ->constrained('brand_products_services')
                ->nullOnDelete();

            $table->index(['organization_id', 'product_service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brand_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_service_id');
        });
    }
};
