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
            $table->integer('achievement_points')->default(0)->after('wallet_balance');
            $table->json('owned_cosmetics')->nullable()->after('achievement_points');
            $table->json('badge_inventory')->nullable()->after('owned_cosmetics');
            $table->string('active_profile_theme')->nullable()->after('badge_inventory');
            $table->string('active_title')->nullable()->after('active_profile_theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'achievement_points',
                'owned_cosmetics',
                'badge_inventory',
                'active_profile_theme',
                'active_title',
            ]);
        });
    }
};
