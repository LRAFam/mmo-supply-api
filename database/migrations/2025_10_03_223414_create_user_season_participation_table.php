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
        Schema::create('user_season_participation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('season_id')->constrained()->onDelete('cascade');
            $table->integer('rank')->nullable();
            $table->decimal('total_sales', 10, 2)->default(0);
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->integer('achievements_unlocked')->default(0);
            $table->boolean('participated')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'season_id'], 'unique_user_season');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_season_participation');
    }
};
