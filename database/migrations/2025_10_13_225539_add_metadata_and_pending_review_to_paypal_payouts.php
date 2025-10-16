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
        Schema::table('paypal_payouts', function (Blueprint $table) {
            // Add metadata column for storing extra data
            $table->json('metadata')->nullable()->after('error_message');
        });

        // Modify status enum to include 'pending_review'
        DB::statement("ALTER TABLE paypal_payouts MODIFY COLUMN status ENUM('pending', 'pending_review', 'success', 'failed') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paypal_payouts', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });

        // Revert status enum
        DB::statement("ALTER TABLE paypal_payouts MODIFY COLUMN status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending'");
    }
};
