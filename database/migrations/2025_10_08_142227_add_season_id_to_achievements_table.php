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
            $table->foreignId('season_id')->nullable()->after('slug')->constrained()->onDelete('set null');
            $table->string('badge_icon')->nullable()->after('icon');
            $table->boolean('is_seasonal')->default(false)->after('is_active');
            $table->boolean('grants_badge')->default(false)->after('is_seasonal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn(['season_id', 'badge_icon', 'is_seasonal', 'grants_badge']);
        });
    }
};
