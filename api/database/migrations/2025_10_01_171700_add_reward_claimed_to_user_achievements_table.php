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
        Schema::table('user_achievements', function (Blueprint $table) {
            $table->boolean('reward_claimed')->default(false)->after('is_notified');
            $table->timestamp('reward_claimed_at')->nullable()->after('reward_claimed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_achievements', function (Blueprint $table) {
            $table->dropColumn(['reward_claimed', 'reward_claimed_at']);
        });
    }
};
