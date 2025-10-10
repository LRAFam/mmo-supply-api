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
        Schema::table('users', function (Blueprint $table) {
            // Add username field for gaming platform
            $table->string('username')->unique()->nullable()->after('name');

            // Modify role enum to include 'system' for AI agent and system users
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'moderator', 'system') DEFAULT 'user'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove username field
            $table->dropColumn('username');

            // Revert role enum to original values
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'admin', 'moderator') DEFAULT 'user'");
        });
    }
};
