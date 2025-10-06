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
            // Check if columns exist before adding
            if (!Schema::hasColumn('items', 'tags')) {
                $table->json('tags')->nullable()->after('images');
            }
            if (!Schema::hasColumn('items', 'delivery_time')) {
                $table->string('delivery_time')->nullable()->after('stock');
            }
            if (!Schema::hasColumn('items', 'delivery_method')) {
                $table->string('delivery_method')->default('trade')->after('delivery_time');
            }
            if (!Schema::hasColumn('items', 'requirements')) {
                $table->text('requirements')->nullable()->after('delivery_method');
            }
            if (!Schema::hasColumn('items', 'warranty_days')) {
                $table->integer('warranty_days')->default(0)->after('requirements');
            }
            if (!Schema::hasColumn('items', 'refund_policy')) {
                $table->string('refund_policy')->default('no_refund')->after('warranty_days');
            }
            if (!Schema::hasColumn('items', 'variants')) {
                $table->json('variants')->nullable()->after('refund_policy');
            }
            if (!Schema::hasColumn('items', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('discount');
            }
            if (!Schema::hasColumn('items', 'min_quantity')) {
                $table->integer('min_quantity')->default(1)->after('stock');
            }
            if (!Schema::hasColumn('items', 'max_quantity')) {
                $table->integer('max_quantity')->nullable()->after('min_quantity');
            }
            if (!Schema::hasColumn('items', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('stock');
            }
            if (!Schema::hasColumn('items', 'auto_deactivate')) {
                $table->boolean('auto_deactivate')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('items', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('name');
            }
            if (!Schema::hasColumn('items', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('items', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('items', 'featured_until')) {
                $table->timestamp('featured_until')->nullable()->after('is_featured');
            }
        });

        // Enhance currencies table
        Schema::table('currencies', function (Blueprint $table) {
            // Add missing base fields first
            if (!Schema::hasColumn('currencies', 'name')) {
                $table->string('name')->nullable()->after('game_id');
            }
            if (!Schema::hasColumn('currencies', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('currencies', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('currencies', 'content')) {
                $table->longText('content')->nullable()->after('description');
            }
            if (!Schema::hasColumn('currencies', 'images')) {
                $table->json('images')->nullable()->after('content');
            }
            if (!Schema::hasColumn('currencies', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('rate');
            }
            if (!Schema::hasColumn('currencies', 'discount')) {
                $table->decimal('discount', 10, 2)->nullable()->after('price');
            }
            if (!Schema::hasColumn('currencies', 'amount')) {
                $table->string('amount')->nullable()->after('stock');
            }
            if (!Schema::hasColumn('currencies', 'delivery_time')) {
                $table->string('delivery_time')->nullable()->after('amount');
            }

            // Add advanced fields
            if (!Schema::hasColumn('currencies', 'tags')) {
                $table->json('tags')->nullable()->after('images');
            }
            if (!Schema::hasColumn('currencies', 'delivery_method')) {
                $table->string('delivery_method')->default('trade')->after('delivery_time');
            }
            if (!Schema::hasColumn('currencies', 'requirements')) {
                $table->text('requirements')->nullable()->after('delivery_method');
            }
            if (!Schema::hasColumn('currencies', 'warranty_days')) {
                $table->integer('warranty_days')->default(0)->after('requirements');
            }
            if (!Schema::hasColumn('currencies', 'refund_policy')) {
                $table->string('refund_policy')->default('no_refund')->after('warranty_days');
            }
            if (!Schema::hasColumn('currencies', 'min_amount')) {
                $table->integer('min_amount')->default(1)->after('amount');
            }
            if (!Schema::hasColumn('currencies', 'max_amount')) {
                $table->integer('max_amount')->nullable()->after('min_amount');
            }
            if (!Schema::hasColumn('currencies', 'bulk_pricing')) {
                $table->json('bulk_pricing')->nullable()->after('max_amount');
            }
            if (!Schema::hasColumn('currencies', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('discount');
            }
            if (!Schema::hasColumn('currencies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('stock');
            }
            if (!Schema::hasColumn('currencies', 'auto_deactivate')) {
                $table->boolean('auto_deactivate')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('currencies', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('name');
            }
            if (!Schema::hasColumn('currencies', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('currencies', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('currencies', 'featured_until')) {
                $table->timestamp('featured_until')->nullable()->after('is_featured');
            }
        });

        // Enhance accounts table
        Schema::table('accounts', function (Blueprint $table) {
            // Add missing base fields
            if (!Schema::hasColumn('accounts', 'account_level')) {
                $table->string('account_level')->nullable()->after('description');
            }
            if (!Schema::hasColumn('accounts', 'rank')) {
                $table->string('rank')->nullable()->after('account_level');
            }
            if (!Schema::hasColumn('accounts', 'account_stats')) {
                $table->text('account_stats')->nullable()->after('rank');
            }
            if (!Schema::hasColumn('accounts', 'delivery_time')) {
                $table->string('delivery_time')->nullable()->after('stock');
            }

            // Add advanced fields
            if (!Schema::hasColumn('accounts', 'tags')) {
                $table->json('tags')->nullable()->after('images');
            }
            if (!Schema::hasColumn('accounts', 'server_region')) {
                $table->string('server_region')->nullable()->after('account_stats');
            }
            if (!Schema::hasColumn('accounts', 'email_included')) {
                $table->boolean('email_included')->default(false)->after('server_region');
            }
            if (!Schema::hasColumn('accounts', 'email_changeable')) {
                $table->boolean('email_changeable')->default(false)->after('email_included');
            }
            if (!Schema::hasColumn('accounts', 'account_age_days')) {
                $table->integer('account_age_days')->nullable()->after('email_changeable');
            }
            if (!Schema::hasColumn('accounts', 'included_items')) {
                $table->text('included_items')->nullable()->after('account_stats');
            }
            if (!Schema::hasColumn('accounts', 'delivery_method')) {
                $table->string('delivery_method')->default('email')->after('delivery_time');
            }
            if (!Schema::hasColumn('accounts', 'requirements')) {
                $table->text('requirements')->nullable()->after('delivery_method');
            }
            if (!Schema::hasColumn('accounts', 'warranty_days')) {
                $table->integer('warranty_days')->default(0)->after('requirements');
            }
            if (!Schema::hasColumn('accounts', 'refund_policy')) {
                $table->string('refund_policy')->default('no_refund')->after('warranty_days');
            }
            if (!Schema::hasColumn('accounts', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('discount');
            }
            if (!Schema::hasColumn('accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('stock');
            }
            if (!Schema::hasColumn('accounts', 'auto_deactivate')) {
                $table->boolean('auto_deactivate')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('accounts', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('accounts', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('accounts', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('accounts', 'featured_until')) {
                $table->timestamp('featured_until')->nullable()->after('is_featured');
            }
        });

        // Enhance services table
        Schema::table('services', function (Blueprint $table) {
            // Add missing base fields
            if (!Schema::hasColumn('services', 'estimated_time')) {
                $table->string('estimated_time')->nullable()->after('content');
            }
            if (!Schema::hasColumn('services', 'delivery_time')) {
                $table->string('delivery_time')->nullable()->after('estimated_time');
            }

            // Add advanced fields
            if (!Schema::hasColumn('services', 'tags')) {
                $table->json('tags')->nullable()->after('images');
            }
            if (!Schema::hasColumn('services', 'packages')) {
                $table->json('packages')->nullable()->after('discount');
            }
            if (!Schema::hasColumn('services', 'addons')) {
                $table->json('addons')->nullable()->after('packages');
            }
            if (!Schema::hasColumn('services', 'requirements')) {
                $table->text('requirements')->nullable()->after('addons');
            }
            if (!Schema::hasColumn('services', 'schedule')) {
                $table->text('schedule')->nullable()->after('requirements');
            }
            if (!Schema::hasColumn('services', 'max_concurrent_orders')) {
                $table->integer('max_concurrent_orders')->default(5)->after('schedule');
            }
            if (!Schema::hasColumn('services', 'delivery_method')) {
                $table->string('delivery_method')->nullable()->after('delivery_time');
            }
            if (!Schema::hasColumn('services', 'warranty_days')) {
                $table->integer('warranty_days')->default(0)->after('delivery_method');
            }
            if (!Schema::hasColumn('services', 'refund_policy')) {
                $table->string('refund_policy')->default('no_refund')->after('warranty_days');
            }
            if (!Schema::hasColumn('services', 'discount_price')) {
                $table->decimal('discount_price', 10, 2)->nullable()->after('discount');
            }
            if (!Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('discount_price');
            }
            if (!Schema::hasColumn('services', 'auto_deactivate')) {
                $table->boolean('auto_deactivate')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('services', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('services', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('services', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('services', 'featured_until')) {
                $table->timestamp('featured_until')->nullable()->after('is_featured');
            }
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
