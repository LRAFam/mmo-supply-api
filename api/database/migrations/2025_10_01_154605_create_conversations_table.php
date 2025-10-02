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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->onDelete('cascade'); // First participant
            $table->foreignId('user_two_id')->constrained('users')->onDelete('cascade'); // Second participant
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null'); // Optional: link to order
            $table->string('subject')->nullable(); // Optional subject line
            $table->timestamp('last_message_at')->nullable(); // For sorting by recent activity
            $table->timestamps();

            // Ensure unique conversation between two users
            $table->unique(['user_one_id', 'user_two_id']);
            $table->index(['user_one_id', 'last_message_at']);
            $table->index(['user_two_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
