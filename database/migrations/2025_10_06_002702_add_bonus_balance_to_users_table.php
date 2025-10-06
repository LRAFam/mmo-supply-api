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
        Schema::table('users', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('users', 'bonus_balance')) {
                $table->decimal('bonus_balance', 10, 2)->default(0)->after('wallet_balance');
            }

            // Add IP tracking for multi-account detection
            if (!Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable()->after('email_verified_at');
            }
            if (!Schema::hasColumn('users', 'signup_ip')) {
                $table->string('signup_ip')->nullable()->after('last_login_ip');
            }

            // Add device fingerprint for multi-account detection
            if (!Schema::hasColumn('users', 'device_fingerprint')) {
                $table->string('device_fingerprint')->nullable()->after('signup_ip');
            }

            // Add account restrictions
            if (!Schema::hasColumn('users', 'can_withdraw')) {
                $table->boolean('can_withdraw')->default(false)->after('device_fingerprint');
            }
            if (!Schema::hasColumn('users', 'withdrawal_eligible_at')) {
                $table->timestamp('withdrawal_eligible_at')->nullable()->after('can_withdraw');
            }
            if (!Schema::hasColumn('users', 'total_purchases')) {
                $table->integer('total_purchases')->default(0)->after('withdrawal_eligible_at');
            }
        });

        // Migrate existing wallet_balance to bonus_balance (since we don't know which is which)
        // In production, you'd analyze the data first
        DB::statement('UPDATE users SET bonus_balance = wallet_balance WHERE wallet_balance > 0 AND bonus_balance = 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bonus_balance',
                'email_verified_at',
                'last_login_ip',
                'signup_ip',
                'device_fingerprint',
                'can_withdraw',
                'withdrawal_eligible_at',
                'total_purchases'
            ]);
        });
    }
};
