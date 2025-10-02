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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->default('🏆'); // Emoji or icon class
            $table->enum('category', ['buyer', 'seller', 'social', 'special'])->default('buyer');
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('bronze');
            $table->integer('points')->default(0); // Points awarded for unlocking
            $table->decimal('wallet_reward', 10, 2)->default(0); // Wallet balance reward
            $table->json('requirements')->nullable(); // JSON criteria for unlocking
            $table->boolean('is_active')->default(true);
            $table->boolean('is_secret')->default(false); // Hidden until unlocked
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
