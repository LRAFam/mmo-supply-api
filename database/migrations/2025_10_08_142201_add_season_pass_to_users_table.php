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
            if (!Schema::hasColumn('users', 'achievement_points')) {
                $table->integer('achievement_points')->default(0)->after('wallet_balance');
            }
            if (!Schema::hasColumn('users', 'owned_cosmetics')) {
                $table->json('owned_cosmetics')->nullable()->after('achievement_points');
            }
            if (!Schema::hasColumn('users', 'badge_inventory')) {
                $table->json('badge_inventory')->nullable()->after('owned_cosmetics');
            }
            if (!Schema::hasColumn('users', 'active_profile_theme')) {
                $table->string('active_profile_theme')->nullable()->after('badge_inventory');
            }
            if (!Schema::hasColumn('users', 'active_title')) {
                $table->string('active_title')->nullable()->after('active_profile_theme');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'achievement_points')) $columnsToDrop[] = 'achievement_points';
            if (Schema::hasColumn('users', 'owned_cosmetics')) $columnsToDrop[] = 'owned_cosmetics';
            if (Schema::hasColumn('users', 'badge_inventory')) $columnsToDrop[] = 'badge_inventory';
            if (Schema::hasColumn('users', 'active_profile_theme')) $columnsToDrop[] = 'active_profile_theme';
            if (Schema::hasColumn('users', 'active_title')) $columnsToDrop[] = 'active_title';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
