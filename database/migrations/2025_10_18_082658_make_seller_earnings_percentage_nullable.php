<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make seller_earnings_percentage nullable so users can use tier-based earnings
            $table->decimal('seller_earnings_percentage', 5, 2)->nullable()->change();
        });

        // Reset all users with default 70% to null so they use tier-based earnings
        // Standard: 80%, Verified: 88%, Premium: 92%
        DB::table('users')
            ->where('seller_earnings_percentage', 70.00)
            ->update(['seller_earnings_percentage' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert to NOT NULL with default of 70
            $table->decimal('seller_earnings_percentage', 5, 2)->default(70.00)->change();
        });
    }
};
