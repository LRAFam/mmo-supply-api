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
        Schema::create('crypto_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->string('payment_id')->unique();
            $table->decimal('amount_usd', 10, 2);
            $table->decimal('crypto_amount', 20, 8);
            $table->string('currency'); // btc, eth, usdt, etc.
            $table->string('status'); // waiting, confirming, confirmed, finished, failed, refunded, expired
            $table->string('payment_address');
            $table->string('payin_extra_id')->nullable(); // For XRP, XLM, etc.
            $table->text('tx_hash')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('payment_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_transactions');
    }
};
