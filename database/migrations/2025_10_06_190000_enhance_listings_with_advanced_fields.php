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
        // Enhance items table
        Schema::table('items', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('images');
            $table->string('delivery_method')->default('trade')->after('delivery_time'); // trade, mail, account
            $table->text('requirements')->nullable()->after('delivery_method');
            $table->integer('warranty_days')->default(0)->after('requirements');
            $table->string('refund_policy')->default('no_refund')->after('warranty_days'); // no_refund, 24h, 7days
            $table->json('variants')->nullable()->after('refund_policy'); // For different tiers/packages
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            $table->integer('min_quantity')->default(1)->after('stock');
            $table->integer('max_quantity')->nullable()->after('min_quantity');
            $table->boolean('auto_deactivate')->default(false)->after('is_active');
            $table->string('seo_title')->nullable()->after('title');
            $table->text('seo_description')->nullable()->after('description');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
        });

        // Enhance currencies table
        Schema::table('currencies', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('images');
            $table->string('delivery_method')->default('trade')->after('images');
            $table->text('requirements')->nullable()->after('delivery_method');
            $table->integer('warranty_days')->default(0)->after('requirements');
            $table->string('refund_policy')->default('no_refund')->after('warranty_days');
            $table->json('bulk_pricing')->nullable()->after('max_amount'); // [{min: 1000000, price: 0.95}]
            $table->decimal('discount_price', 10, 2)->nullable()->after('price_per_unit');
            $table->boolean('auto_deactivate')->default(false)->after('is_active');
            $table->string('seo_title')->nullable()->after('name');
            $table->text('seo_description')->nullable()->after('description');
            $table->timestamp('featured_until')->nullable()->after('is_active');
        });

        // Enhance accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('account_stats');
            $table->string('server_region')->nullable()->after('tags');
            $table->boolean('email_included')->default(false)->after('server_region');
            $table->boolean('email_changeable')->default(false)->after('email_included');
            $table->integer('account_age_days')->nullable()->after('email_changeable');
            $table->integer('warranty_days')->default(0)->after('account_age_days');
            $table->string('refund_policy')->default('no_refund')->after('warranty_days');
            $table->text('requirements')->nullable()->after('refund_policy');
            $table->json('included_items')->nullable()->after('account_stats'); // Skins, characters, etc.
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            $table->boolean('auto_deactivate')->default(false)->after('is_active');
            $table->string('seo_title')->nullable()->after('title');
            $table->text('seo_description')->nullable()->after('description');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
        });

        // Enhance services table
        Schema::table('services', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('images');
            $table->json('packages')->nullable()->after('price'); // [{name: 'Basic', price: 10, features: []}]
            $table->json('addons')->nullable()->after('packages'); // [{name: 'Extra boost', price: 5}]
            $table->text('requirements')->nullable()->after('addons');
            $table->json('schedule')->nullable()->after('requirements'); // Availability hours
            $table->integer('max_concurrent_orders')->default(5)->after('schedule');
            $table->string('delivery_method')->nullable()->after('estimated_time');
            $table->integer('warranty_days')->default(0)->after('delivery_method');
            $table->string('refund_policy')->default('no_refund')->after('warranty_days');
            $table->decimal('discount_price', 10, 2)->nullable()->after('price');
            $table->boolean('auto_deactivate')->default(false)->after('is_active');
            $table->string('seo_title')->nullable()->after('title');
            $table->text('seo_description')->nullable()->after('description');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'tags', 'delivery_method', 'requirements', 'warranty_days', 'refund_policy',
                'variants', 'discount_price', 'min_quantity', 'max_quantity', 'auto_deactivate',
                'seo_title', 'seo_description', 'featured_until'
            ]);
        });

        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn([
                'tags', 'delivery_method', 'requirements', 'warranty_days', 'refund_policy',
                'bulk_pricing', 'discount_price', 'auto_deactivate',
                'seo_title', 'seo_description', 'featured_until'
            ]);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'tags', 'server_region', 'email_included', 'email_changeable', 'account_age_days',
                'warranty_days', 'refund_policy', 'requirements', 'included_items',
                'discount_price', 'auto_deactivate', 'seo_title', 'seo_description', 'featured_until'
            ]);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'tags', 'packages', 'addons', 'requirements', 'schedule', 'max_concurrent_orders',
                'delivery_method', 'warranty_days', 'refund_policy', 'discount_price',
                'auto_deactivate', 'seo_title', 'seo_description', 'featured_until'
            ]);
        });
    }
};
