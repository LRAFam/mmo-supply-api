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
        Schema::table('achievements', function (Blueprint $table) {
            if (!Schema::hasColumn('achievements', 'season_id')) {
                $table->foreignId('season_id')->nullable()->after('slug')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('achievements', 'badge_icon')) {
                $table->string('badge_icon')->nullable()->after('icon');
            }
            if (!Schema::hasColumn('achievements', 'is_seasonal')) {
                $table->boolean('is_seasonal')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('achievements', 'grants_badge')) {
                $table->boolean('grants_badge')->default(false)->after('is_seasonal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            if (Schema::hasColumn('achievements', 'season_id')) {
                $table->dropForeign(['season_id']);
            }

            $columnsToDrop = [];
            if (Schema::hasColumn('achievements', 'season_id')) $columnsToDrop[] = 'season_id';
            if (Schema::hasColumn('achievements', 'badge_icon')) $columnsToDrop[] = 'badge_icon';
            if (Schema::hasColumn('achievements', 'is_seasonal')) $columnsToDrop[] = 'is_seasonal';
            if (Schema::hasColumn('achievements', 'grants_badge')) $columnsToDrop[] = 'grants_badge';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
