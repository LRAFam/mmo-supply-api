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
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('title')->after('game_id');
            $table->text('description')->nullable()->after('title');
            $table->string('image_url')->after('description');
            $table->string('link_url')->after('image_url');
            $table->boolean('is_active')->default(true)->after('payment_status');
            $table->unsignedBigInteger('impressions')->default(0)->after('is_active');
            $table->unsignedBigInteger('clicks')->default(0)->after('impressions');
            $table->enum('placement', ['homepage_top', 'homepage_sidebar', 'marketplace_top', 'marketplace_sidebar', 'game_page_top'])->after('position');
            $table->decimal('ctr', 5, 2)->default(0)->after('clicks')->comment('Click-through rate percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'image_url', 'link_url', 'is_active', 'impressions', 'clicks', 'placement', 'ctr']);
        });
    }
};
