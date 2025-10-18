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
        // Add bonus_balance column to wallets table
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('bonus_balance', 10, 2)->default(0)->after('pending_balance');
        });

        // Migrate data from users.bonus_balance to wallets.bonus_balance
        $users = DB::table('users')
            ->whereNotNull('bonus_balance')
            ->where('bonus_balance', '>', 0)
            ->get();

        foreach ($users as $user) {
            DB::table('wallets')
                ->where('user_id', $user->id)
                ->update(['bonus_balance' => $user->bonus_balance]);
        }

        // Remove bonus_balance column from users table
        // NOTE: Commenting this out for safety - run manually after verifying data migration
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn('bonus_balance');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore bonus_balance to users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('bonus_balance', 10, 2)->default(0);
        });

        // Migrate data back from wallets.bonus_balance to users.bonus_balance
        $wallets = DB::table('wallets')
            ->whereNotNull('bonus_balance')
            ->where('bonus_balance', '>', 0)
            ->get();

        foreach ($wallets as $wallet) {
            DB::table('users')
                ->where('id', $wallet->user_id)
                ->update(['bonus_balance' => $wallet->bonus_balance]);
        }

        // Remove bonus_balance from wallets table
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('bonus_balance');
        });
    }
};
