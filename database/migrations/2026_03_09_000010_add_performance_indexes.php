<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at', 'idx_orders_created_at');
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id', 'idx_categories_parent_id');
        });

        Schema::table('return_requests', function (Blueprint $table) {
            $table->index(['order_id', 'status'], 'idx_return_requests_order_status');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->index('is_read', 'idx_contact_messages_is_read');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->index(['is_active', 'expires_at'], 'idx_coupons_active_expires');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_created_at');
            $table->dropIndex('idx_orders_user_status');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_parent_id');
        });

        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropIndex('idx_return_requests_order_status');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex('idx_contact_messages_is_read');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex('idx_coupons_active_expires');
        });
    }
};
