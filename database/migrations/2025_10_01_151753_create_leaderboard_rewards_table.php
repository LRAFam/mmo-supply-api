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
        Schema::create('leaderboard_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('period', ['weekly', 'monthly']); // Competition period
            $table->date('period_start'); // Start date of the period
            $table->date('period_end'); // End date of the period
            $table->integer('rank'); // Final ranking position (1, 2, 3, etc.)
            $table->decimal('sales_amount', 10, 2); // Sales that earned the rank
            $table->decimal('reward_amount', 10, 2); // Cash reward credited
            $table->string('badge')->nullable(); // Special badge earned (gold, silver, bronze)
            $table->boolean('credited')->default(false); // Whether reward was paid out
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period', 'period_start']);
            $table->index(['period', 'period_start', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaderboard_rewards');
    }
};
