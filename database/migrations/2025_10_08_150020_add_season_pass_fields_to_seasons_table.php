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
        Schema::table('seasons', function (Blueprint $table) {
            if (!Schema::hasColumn('seasons', 'pass_revenue')) {
                $table->decimal('pass_revenue', 10, 2)->default(0)->after('prize_pool');
            }
            if (!Schema::hasColumn('seasons', 'rewards_paid')) {
                $table->decimal('rewards_paid', 10, 2)->default(0)->after('pass_revenue');
            }
            if (!Schema::hasColumn('seasons', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seasons', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('seasons', 'pass_revenue')) $columnsToDrop[] = 'pass_revenue';
            if (Schema::hasColumn('seasons', 'rewards_paid')) $columnsToDrop[] = 'rewards_paid';
            if (Schema::hasColumn('seasons', 'is_active')) $columnsToDrop[] = 'is_active';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
