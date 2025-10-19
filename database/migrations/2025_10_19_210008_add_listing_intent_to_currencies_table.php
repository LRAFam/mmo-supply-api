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
            // Add listing_intent field for buy/sell toggle
            $table->string('listing_intent')->default('selling')->after('price_per_million');

            // Add min/max quantity fields for OSRS currency
            $table->integer('min_quantity')->nullable()->after('listing_intent');
            $table->integer('max_quantity')->nullable()->after('min_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['listing_intent', 'min_quantity', 'max_quantity']);
        });
    }
};
