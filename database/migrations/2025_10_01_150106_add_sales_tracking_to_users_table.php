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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('monthly_sales', 10, 2)->default(0)->after('creator_earnings_percentage');
            $table->decimal('lifetime_sales', 10, 2)->default(0)->after('monthly_sales');
            $table->timestamp('monthly_sales_reset_at')->nullable()->after('lifetime_sales');
            $table->enum('auto_tier', ['standard', 'verified', 'premium'])->default('standard')->after('creator_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['monthly_sales', 'lifetime_sales', 'monthly_sales_reset_at', 'auto_tier']);
        });
    }
};
