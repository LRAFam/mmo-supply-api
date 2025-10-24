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
            $table->string('crypto_address')->nullable()->after('paypal_data');
            $table->string('crypto_currency')->default('btc')->after('crypto_address');
            $table->boolean('crypto_enabled')->default(false)->after('crypto_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['crypto_address', 'crypto_currency', 'crypto_enabled']);
        });
    }
};
