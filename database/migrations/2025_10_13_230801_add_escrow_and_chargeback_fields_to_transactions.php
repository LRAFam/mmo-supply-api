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
        Schema::table('transactions', function (Blueprint $table) {
            // Escrow/hold tracking
            $table->boolean('is_held')->default(false)->after('status');
            $table->timestamp('hold_until')->nullable()->after('is_held');
            $table->string('hold_reason')->nullable()->after('hold_until');

            // Chargeback tracking
            $table->boolean('has_chargeback')->default(false)->after('hold_reason');
            $table->timestamp('chargeback_date')->nullable()->after('has_chargeback');
            $table->decimal('chargeback_amount', 10, 2)->nullable()->after('chargeback_date');
            $table->text('chargeback_reason')->nullable()->after('chargeback_amount');
            $table->enum('chargeback_status', ['pending', 'won', 'lost', 'reversed'])->nullable()->after('chargeback_reason');

            // Risk assessment
            $table->integer('risk_score')->default(0)->after('chargeback_status');
            $table->json('risk_factors')->nullable()->after('risk_score');
        });

        // Add indexes for common queries
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('is_held');
            $table->index('hold_until');
            $table->index('has_chargeback');
            $table->index(['user_id', 'is_held']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['transactions_is_held_index']);
            $table->dropIndex(['transactions_hold_until_index']);
            $table->dropIndex(['transactions_has_chargeback_index']);
            $table->dropIndex(['transactions_user_id_is_held_index']);

            $table->dropColumn([
                'is_held',
                'hold_until',
                'hold_reason',
                'has_chargeback',
                'chargeback_date',
                'chargeback_amount',
                'chargeback_reason',
                'chargeback_status',
                'risk_score',
                'risk_factors',
            ]);
        });
    }
};
