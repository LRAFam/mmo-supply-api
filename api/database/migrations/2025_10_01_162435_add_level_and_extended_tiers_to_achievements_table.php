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
        Schema::table('achievements', function (Blueprint $table) {
            // Add level field for multi-tier progression (1-10+)
            $table->integer('level')->default(1)->after('tier');

            // Change tier enum to support extended tiers
            $table->dropColumn('tier');
        });

        // Add tier back with extended options
        Schema::table('achievements', function (Blueprint $table) {
            $table->enum('tier', [
                'copper', 'bronze', 'silver', 'gold', 'emerald',
                'sapphire', 'ruby', 'diamond', 'master', 'grandmaster'
            ])->default('copper')->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn('level');
            $table->dropColumn('tier');
        });

        // Restore original tier enum
        Schema::table('achievements', function (Blueprint $table) {
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('bronze')->after('category');
        });
    }
};
