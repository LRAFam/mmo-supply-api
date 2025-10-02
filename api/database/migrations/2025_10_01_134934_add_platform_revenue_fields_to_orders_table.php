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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('platform_fee_percentage', 5, 2)->default(10.00)->after('total');
            $table->decimal('platform_fee', 10, 2)->default(0)->after('platform_fee_percentage');
            $table->decimal('seller_earnings', 10, 2)->default(0)->after('platform_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['platform_fee_percentage', 'platform_fee', 'seller_earnings']);
        });
    }
};
