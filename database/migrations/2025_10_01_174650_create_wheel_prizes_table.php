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
        Schema::create('wheel_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spin_wheel_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "$5 Credit", "Better Luck Next Time"
            $table->string('type'); // wallet_credit, discount_code, featured_time, nothing
            $table->decimal('value', 10, 2)->default(0);
            $table->integer('probability_weight')->default(1); // Higher = more likely
            $table->string('color')->default('#3b82f6'); // Hex color for wheel segment
            $table->string('icon')->nullable(); // Icon/emoji for prize
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wheel_prizes');
    }
};
