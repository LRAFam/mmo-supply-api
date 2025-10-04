<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'referral_commission' to the type enum
        DB::statement("ALTER TABLE `transactions` MODIFY COLUMN `type` ENUM('deposit', 'withdrawal', 'purchase', 'sale', 'refund', 'fee', 'commission', 'achievement', 'referral_commission')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'referral_commission' from the enum
        DB::statement("ALTER TABLE `transactions` MODIFY COLUMN `type` ENUM('deposit', 'withdrawal', 'purchase', 'sale', 'refund', 'fee', 'commission', 'achievement')");
    }
};
