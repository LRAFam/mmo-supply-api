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
        Schema::table('spin_results', function (Blueprint $table) {
            if (!Schema::hasColumn('spin_results', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('spun_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spin_results', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
