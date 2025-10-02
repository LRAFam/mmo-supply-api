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
        // Add fields to games table
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('provider_count');
            $table->boolean('is_active')->default(true)->after('is_featured');
        });

        // Add fields to items table
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('stock');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->string('delivery_time')->nullable()->after('is_featured');
        });

        // Add fields to currencies table
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('name')->nullable()->after('game_id');
            $table->string('slug')->nullable()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->decimal('price_per_unit', 10, 2)->default(0)->after('rate');
            $table->integer('min_amount')->default(1)->after('price_per_unit');
            $table->integer('max_amount')->default(999999)->after('min_amount');
            $table->json('images')->nullable()->after('max_amount');
            $table->boolean('is_active')->default(true)->after('images');
        });

        // Add fields to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('stock');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->string('account_level')->nullable()->after('is_featured');
            $table->json('account_stats')->nullable()->after('account_level');
        });

        // Add fields to services table
        Schema::table('services', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->boolean('is_active')->default(true)->after('discount');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->string('estimated_time')->nullable()->after('is_featured');
        });

        // Add fields to providers table
        Schema::table('providers', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0)->after('vouches');
            $table->integer('total_sales')->default(0)->after('rating');
            $table->boolean('is_verified')->default(false)->after('total_sales');
        });

        // Add fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_seller')->default(false)->after('password');
            $table->string('avatar')->nullable()->after('is_seller');
            $table->text('bio')->nullable()->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'is_active']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_featured', 'delivery_time']);
        });

        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['name', 'slug', 'description', 'price_per_unit', 'min_amount', 'max_amount', 'images', 'is_active']);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_featured', 'account_level', 'account_stats']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_active', 'is_featured', 'estimated_time']);
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['rating', 'total_sales', 'is_verified']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_seller', 'avatar', 'bio']);
        });
    }
};
