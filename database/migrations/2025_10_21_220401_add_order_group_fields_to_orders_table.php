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
        Schema::table('orders', function (Blueprint $table) {
            // Add seller_id - each order belongs to ONE seller (if not exists)
            // Made nullable to support existing orders in production
            if (!Schema::hasColumn('orders', 'seller_id')) {
                $table->foreignId('seller_id')->after('user_id')->nullable()->constrained('users')->onDelete('cascade');
            }

            // Add order_group_id - links orders from same checkout
            if (!Schema::hasColumn('orders', 'order_group_id')) {
                $table->string('order_group_id')->after('order_number')->nullable()->index();
            }

            // Add cart_id reference - track which cart this order came from
            if (!Schema::hasColumn('orders', 'cart_id')) {
                $table->foreignId('cart_id')->after('order_group_id')->nullable()->constrained()->onDelete('set null');
            }

            // Add Stripe Transfer ID - for tracking platform fee transfers
            if (!Schema::hasColumn('orders', 'stripe_transfer_id')) {
                $table->string('stripe_transfer_id')->after('stripe_payment_intent_id')->nullable();
            }

            // Add seller_payout (platform_fee already exists)
            if (!Schema::hasColumn('orders', 'seller_payout')) {
                $table->decimal('seller_payout', 10, 2)->after('platform_fee')->default(0);
            }

            // Add indexes for common queries (check if they don't already exist)
            // Note: Laravel doesn't have a hasIndex helper, so we'll try/catch
        });

        // Add indexes separately to avoid duplicates
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['seller_id', 'status']);
            });
        } catch (\Exception $e) {
            // Index already exists
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['order_group_id']);
            });
        } catch (\Exception $e) {
            // Index already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['seller_id', 'status']);
            $table->dropIndex(['order_group_id']);

            // Drop foreign keys
            $table->dropForeign(['seller_id']);
            $table->dropForeign(['cart_id']);

            // Drop columns
            $table->dropColumn([
                'seller_id',
                'order_group_id',
                'cart_id',
                'stripe_transfer_id',
                'platform_fee',
                'seller_payout'
            ]);
        });
    }
};
