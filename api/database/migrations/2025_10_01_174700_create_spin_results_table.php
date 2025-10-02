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
        Schema::create('spin_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('spin_wheel_id')->constrained()->onDelete('cascade');
            $table->foreignId('wheel_prize_id')->constrained()->onDelete('cascade');
            $table->string('prize_name');
            $table->string('prize_type'); // wallet_credit, discount_code, featured_time, nothing
            $table->decimal('prize_value', 10, 2)->default(0);
            $table->timestamp('spun_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spin_results');
    }
};
