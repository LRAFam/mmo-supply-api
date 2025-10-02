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
            $table->integer('premium_spins_remaining')->default(0)->after('wallet_balance');
            $table->timestamp('premium_spins_reset_at')->nullable()->after('premium_spins_remaining');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['premium_spins_remaining', 'premium_spins_reset_at']);
        });
    }
};
