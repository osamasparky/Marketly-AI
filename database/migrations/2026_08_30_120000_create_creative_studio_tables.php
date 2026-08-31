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
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->index();
            $table->unsignedBigInteger('content_post_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->string('title');
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_type', 50)->default('graphic_card'); // graphic_card, image, video_script, svg
            $table->string('mime_type', 50)->default('image/svg+xml');
            $table->unsignedBigInteger('file_size_bytes')->default(0);

            $table->integer('width')->default(1080);
            $table->integer('height')->default(1080);
            $table->string('aspect_ratio', 20)->default('1:1'); // 1:1, 4:5, 9:16, 16:9

            $table->text('prompt_used')->nullable();
            $table->string('visual_style', 50)->default('branded_quote'); // branded_quote, product_spotlight, metric_card, gradient_banner
            $table->text('text_overlay')->nullable();
            $table->json('color_palette')->nullable(); // primary, secondary, accent, background
            $table->json('metadata')->nullable(); // structured script scenes, layers, SVG content, AI raw output

            $table->enum('status', ['ready', 'processing', 'failed'])->default('ready')->index();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('content_post_id')->references('id')->on('content_posts')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
