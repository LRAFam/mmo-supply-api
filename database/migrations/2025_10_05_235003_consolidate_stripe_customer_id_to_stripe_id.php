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
        // First, migrate any data from stripe_customer_id to stripe_id where stripe_id is null
        DB::statement('UPDATE users SET stripe_id = stripe_customer_id WHERE stripe_customer_id IS NOT NULL AND stripe_id IS NULL');

        // Drop the redundant stripe_customer_id column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the stripe_customer_id column
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('email');
        });

        // Copy data back from stripe_id to stripe_customer_id
        DB::statement('UPDATE users SET stripe_customer_id = stripe_id WHERE stripe_id IS NOT NULL');
    }
};
