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
            $table->string('discord_verification_code', 50)->nullable()->after('referral_code');
            $table->timestamp('discord_verification_code_expires_at')->nullable()->after('discord_verification_code');
            $table->string('discord_guild_id')->nullable()->after('discord_verification_code_expires_at');
            $table->string('discord_channel_id')->nullable()->after('discord_guild_id');
            $table->timestamp('discord_registered_at')->nullable()->after('discord_channel_id');
            $table->boolean('discord_notifications_enabled')->default(true)->after('discord_registered_at');

            // Add index for faster lookups
            $table->index('discord_verification_code');
            $table->index('discord_guild_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['discord_verification_code']);
            $table->dropIndex(['discord_guild_id']);

            $table->dropColumn([
                'discord_verification_code',
                'discord_verification_code_expires_at',
                'discord_guild_id',
                'discord_channel_id',
                'discord_registered_at',
                'discord_notifications_enabled',
            ]);
        });
    }
};
