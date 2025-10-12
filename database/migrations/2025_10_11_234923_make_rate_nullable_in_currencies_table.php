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
            // Make rate nullable since it's been replaced by price_per_unit
            // This is a legacy field that's no longer used by the controller
            $table->float('rate')->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            // Revert rate to non-nullable (though this may fail if null values exist)
            $table->float('rate')->nullable(false)->change();
        });
    }
};
