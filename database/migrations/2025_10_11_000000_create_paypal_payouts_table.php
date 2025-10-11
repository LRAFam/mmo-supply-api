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
        Schema::create('paypal_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');

            // PayPal payout identifiers
            $table->string('payout_batch_id')->unique();
            $table->string('sender_batch_id')->unique();
            $table->string('payout_item_id')->nullable()->unique();

            // PayPal account
            $table->string('paypal_email');

            // Amounts
            $table->decimal('amount', 10, 2); // Gross amount
            $table->decimal('fee', 10, 2); // PayPal fee (2%)
            $table->decimal('net_amount', 10, 2); // Amount recipient receives

            // Status
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paypal_payouts');
    }
};
