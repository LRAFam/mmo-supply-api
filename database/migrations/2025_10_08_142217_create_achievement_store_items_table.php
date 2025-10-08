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
        Schema::create('achievement_store_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('category', ['profile_theme', 'badge', 'title', 'frame', 'username_effect', 'marketplace_perk', 'listing_boost', 'functional', 'social', 'seasonal']);
            $table->string('icon')->nullable();
            $table->integer('points_cost');
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common');
            $table->json('metadata')->nullable(); // Store item-specific data
            $table->boolean('is_active')->default(true);
            $table->boolean('is_limited')->default(false);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('cooldown_days')->nullable(); // For time-limited items
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievement_store_items');
    }
};
