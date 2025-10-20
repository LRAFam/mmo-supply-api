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
        Schema::table('conversations', function (Blueprint $table) {
            // Drop the old unique constraint
            $table->dropUnique('conversations_user_one_id_user_two_id_unique');

            // Add new unique constraint that includes order_id
            // This allows multiple conversations between same users (one per order)
            $table->unique(['user_one_id', 'user_two_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique(['user_one_id', 'user_two_id', 'order_id']);

            // Restore old constraint
            $table->unique(['user_one_id', 'user_two_id']);
        });
    }
};
