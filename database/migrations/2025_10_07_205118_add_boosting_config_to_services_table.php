<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds boosting_config and service_type to services table
     * Reuses existing packages/addons fields for rank tiers and options
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'service_type')) {
                $table->string('service_type')->default('standard')->after('game_id')
                    ->comment('standard, rank_boosting, coaching, etc.');
            }

            if (!Schema::hasColumn('services', 'boosting_config')) {
                $table->json('boosting_config')->nullable()->after('addons')
                    ->comment('Rank boosting specific config: servers, multipliers, rank ranges');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['service_type', 'boosting_config']);
        });
    }
};
