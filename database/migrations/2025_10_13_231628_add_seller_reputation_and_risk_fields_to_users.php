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
            // Seller reputation and trust metrics
            $table->integer('seller_reputation_score')->default(0)->after('can_withdraw');
            $table->integer('completed_sales')->default(0)->after('seller_reputation_score');
            $table->integer('disputed_sales')->default(0)->after('completed_sales');
            $table->integer('chargebacks_received')->default(0)->after('disputed_sales');

            // Trust level for graduated hold periods
            $table->enum('trust_level', ['new', 'standard', 'trusted', 'verified'])->default('new')->after('chargebacks_received');

            // Payout restrictions
            $table->integer('payout_hold_days')->default(14)->after('trust_level'); // Days to hold funds
            $table->decimal('chargeback_reserve_percent', 5, 2)->default(10.00)->after('payout_hold_days'); // % to hold as reserve

            // Dates for tracking
            $table->timestamp('first_sale_at')->nullable()->after('chargeback_reserve_percent');
            $table->timestamp('last_chargeback_at')->nullable()->after('first_sale_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('trust_level');
            $table->index('seller_reputation_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['users_trust_level_index']);
            $table->dropIndex(['users_seller_reputation_score_index']);

            $table->dropColumn([
                'seller_reputation_score',
                'completed_sales',
                'disputed_sales',
                'chargebacks_received',
                'trust_level',
                'payout_hold_days',
                'chargeback_reserve_percent',
                'first_sale_at',
                'last_chargeback_at',
            ]);
        });
    }
};
