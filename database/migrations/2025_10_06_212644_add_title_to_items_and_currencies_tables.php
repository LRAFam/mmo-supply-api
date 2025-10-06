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
        // Add title column to items table
        if (!Schema::hasColumn('items', 'title')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('title')->nullable()->after('name');
            });
        }

        // Copy data from name to title for items
        DB::statement('UPDATE items SET title = name WHERE title IS NULL');

        // Add title column to currencies table
        if (!Schema::hasColumn('currencies', 'title')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->string('title')->nullable()->after('name');
            });
        }

        // Copy data from name to title for currencies
        DB::statement('UPDATE currencies SET title = name WHERE title IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('items', 'title')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }

        if (Schema::hasColumn('currencies', 'title')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};
