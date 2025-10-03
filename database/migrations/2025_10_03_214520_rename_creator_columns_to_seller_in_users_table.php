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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('creator_earnings_percentage', 'seller_earnings_percentage');
            $table->renameColumn('creator_tier', 'seller_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('seller_earnings_percentage', 'creator_earnings_percentage');
            $table->renameColumn('seller_tier', 'creator_tier');
        });
    }
};
