<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('upcoming', 'active', 'completed', 'cancelled', 'draft') DEFAULT 'upcoming'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any draft events to upcoming before removing the enum value
        DB::table('events')->where('status', 'draft')->update(['status' => 'upcoming']);
        DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('upcoming', 'active', 'completed', 'cancelled') DEFAULT 'upcoming'");
    }
};
