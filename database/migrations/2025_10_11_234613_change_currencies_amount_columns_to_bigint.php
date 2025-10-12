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
        Schema::table('currencies', function (Blueprint $table) {
            // Change min_amount and max_amount from INT to BIGINT to support large values
            // This is necessary for games like OSRS where platinum tokens allow trading
            // hundreds of billions of GP in a single transaction (1 plat token = 1000 GP)
            $table->unsignedBigInteger('min_amount')->default(1)->change();
            $table->unsignedBigInteger('max_amount')->default(999999)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Revert back to integer (though this may cause data loss if large values exist)
            $table->integer('min_amount')->default(1)->change();
            $table->integer('max_amount')->default(999999)->change();
        });
    }
};
