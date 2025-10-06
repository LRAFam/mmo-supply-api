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
        // Drop the redundant seller_subscriptions table
        // All subscriptions are now managed by Cashier's subscriptions table
        Schema::dropIfExists('seller_subscriptions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the table if needed to rollback
        Schema::create('seller_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('tier', ['basic', 'premium', 'elite'])->default('basic');
            $table->decimal('fee_percentage', 5, 2)->default(10.00);
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('stripe_subscription_id')->nullable();
            $table->timestamps();
        });
    }
};
