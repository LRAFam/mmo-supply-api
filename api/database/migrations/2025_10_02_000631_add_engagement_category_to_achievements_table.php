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
        // Alter the enum to add 'engagement' category
        \DB::statement("ALTER TABLE achievements MODIFY COLUMN category ENUM('buyer', 'seller', 'social', 'special', 'engagement') DEFAULT 'buyer'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        \DB::statement("ALTER TABLE achievements MODIFY COLUMN category ENUM('buyer', 'seller', 'social', 'special') DEFAULT 'buyer'");
    }
};
