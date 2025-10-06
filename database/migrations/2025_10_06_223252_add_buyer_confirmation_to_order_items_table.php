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
        Schema::table('order_items', function (Blueprint $table) {
            // Buyer confirmation fields
            $table->boolean('buyer_confirmed')->default(false)->after('delivered_at');
            $table->timestamp('buyer_confirmed_at')->nullable()->after('buyer_confirmed');
            $table->text('buyer_confirmation_notes')->nullable()->after('buyer_confirmed_at');

            // Auto-release tracking
            $table->timestamp('auto_release_at')->nullable()->after('buyer_confirmation_notes');
            $table->boolean('auto_released')->default(false)->after('auto_release_at');

            // Fund release tracking
            $table->boolean('funds_released')->default(false)->after('auto_released');
            $table->timestamp('funds_released_at')->nullable()->after('funds_released');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_confirmed',
                'buyer_confirmed_at',
                'buyer_confirmation_notes',
                'auto_release_at',
                'auto_released',
                'funds_released',
                'funds_released_at',
            ]);
        });
    }
};
