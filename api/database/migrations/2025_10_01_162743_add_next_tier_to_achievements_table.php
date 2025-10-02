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
            // Add field to link to next tier achievement
            $table->unsignedBigInteger('next_tier_id')->nullable()->after('level');
            $table->foreign('next_tier_id')->references('id')->on('achievements')->onDelete('set null');

            // Add base achievement group name
            $table->string('achievement_group')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropForeign(['next_tier_id']);
            $table->dropColumn('next_tier_id');
            $table->dropColumn('achievement_group');
        });
    }
};
