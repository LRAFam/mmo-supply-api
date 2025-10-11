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
        // Add indexes only for tables that exist and don't have them yet
        // These indexes will significantly improve query performance on frequently accessed columns

        // Users table indexes for seller and role lookups
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'monthly_sales')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('monthly_sales', 'idx_users_monthly_sales');
                $table->index('role', 'idx_users_role');
            });
        }

        // Notifications table index for user lookups (if table exists)
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['user_id', 'read_at'], 'idx_notifications_user_unread');
            });
        }

        // Messages table indexes for conversation queries (if table exists)
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->index('conversation_id', 'idx_messages_conversation_id');
                $table->index('sender_id', 'idx_messages_sender_id');
            });
        }

        // Note: Orders, order_items, items, currencies, accounts, and services tables
        // already have the necessary indexes from their original migrations.
        // If additional indexes are needed, they should be added in separate migrations
        // to avoid conflicts.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_role');
                $table->dropIndex('idx_users_monthly_sales');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notifications_user_unread');
            });
        }

        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropIndex('idx_messages_conversation_id');
                $table->dropIndex('idx_messages_sender_id');
            });
        }
    }
};
