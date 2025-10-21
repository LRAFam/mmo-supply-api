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
            // Discord profile data
            $table->string('discord_banner')->nullable()->after('discord_avatar');
            $table->string('discord_accent_color')->nullable()->after('discord_banner');

            // Custom S3 uploaded profile images (take priority over Discord)
            $table->string('custom_avatar')->nullable()->after('discord_accent_color');
            $table->string('custom_banner')->nullable()->after('custom_avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'discord_banner',
                'discord_accent_color',
                'custom_avatar',
                'custom_banner'
            ]);
        });
    }
};
