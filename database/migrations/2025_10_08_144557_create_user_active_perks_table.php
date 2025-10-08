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
        Schema::create('user_active_perks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_item_id')->constrained('achievement_store_items')->onDelete('cascade');
            $table->string('perk_type'); // listing_boost, featured_discount, commission_reduction, etc.
            $table->json('perk_data')->nullable(); // Store perk-specific data (discount %, boost duration, etc.)
            $table->timestamp('activated_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('uses_remaining')->nullable(); // For limited-use perks
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_active_perks');
    }
};
