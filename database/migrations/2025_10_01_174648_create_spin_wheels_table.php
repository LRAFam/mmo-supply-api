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
        Schema::create('spin_wheels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Daily Wheel", "Premium Wheel"
            $table->string('type')->default('free'); // free, premium
            $table->decimal('cost', 10, 2)->default(0); // Cost to spin (0 for free daily)
            $table->integer('cooldown_hours')->default(24); // Hours between free spins
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spin_wheels');
    }
};
