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
            // Stripe Connect fields
            $table->string('stripe_connect_id')->nullable()->after('is_seller');
            $table->boolean('stripe_connect_enabled')->default(false)->after('stripe_connect_id');
            $table->json('stripe_connect_data')->nullable()->after('stripe_connect_enabled');

            // PayPal fields
            $table->string('paypal_merchant_id')->nullable()->after('stripe_connect_data');
            $table->boolean('paypal_enabled')->default(false)->after('paypal_merchant_id');
            $table->json('paypal_data')->nullable()->after('paypal_enabled');

            // Payment method preferences
            $table->json('payment_methods')->nullable()->after('paypal_data')->comment('Which payment methods the seller accepts (stripe, paypal, or both)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_connect_id',
                'stripe_connect_enabled',
                'stripe_connect_data',
                'paypal_merchant_id',
                'paypal_enabled',
                'paypal_data',
                'payment_methods'
            ]);
        });
    }
};
