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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->enum('type', ['drop_party', 'tournament', 'sale', 'giveaway', 'special'])->default('giveaway');
            $table->foreignId('game_id')->nullable()->constrained()->onDelete('cascade'); // Game specific events
            $table->string('banner_image')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->enum('status', ['upcoming', 'active', 'completed', 'cancelled'])->default('upcoming');
            $table->integer('max_participants')->nullable(); // Null = unlimited
            $table->integer('winner_count')->default(1); // How many winners
            $table->json('prizes')->nullable(); // Array of prizes (wallet credits, items, etc.)
            $table->json('rules')->nullable(); // Event specific rules
            $table->json('requirements')->nullable(); // Requirements to participate (min level, min purchases, etc.)
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index(['game_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
