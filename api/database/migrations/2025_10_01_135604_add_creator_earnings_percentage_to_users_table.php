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
            // Creator earnings percentage (70% = seller gets 70%, platform gets 30%)
            $table->decimal('creator_earnings_percentage', 5, 2)->default(70.00)->after('stripe_onboarding_complete');
            // Seller tier for display purposes
            $table->enum('creator_tier', ['standard', 'partner', 'elite'])->default('standard')->after('creator_earnings_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['creator_earnings_percentage', 'creator_tier']);
        });
    }
};
