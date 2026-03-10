<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Delete orphaned order_status_history (references deleted orders)
        DB::table('order_status_history')
            ->whereNotIn('order_id', DB::table('orders')->select('id'))
            ->delete();

        // Step 2: Delete orphaned checkout_details (references deleted orders)
        DB::table('checkout_details')
            ->whereNotIn('order_id', DB::table('orders')->select('id'))
            ->delete();

        // Step 3: Delete orphaned orders (references deleted users)
        DB::table('orders')
            ->whereNotIn('user_id', DB::table('user')->select('id'))
            ->delete();

        // Step 4: Delete orphaned order_details (references deleted orders)
        DB::table('order_details')
            ->whereNotIn('order_id', DB::table('orders')->select('id'))
            ->delete();

        // Step 5: Drop legacy 'users' table (empty, replaced by 'user')
        Schema::dropIfExists('users');

        // Step 6: Drop legacy 'password_resets' table (replaced by password_reset_tokens)
        Schema::dropIfExists('password_resets');
    }

    public function down(): void
    {
        // Re-create legacy tables (empty)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Note: Orphaned records cannot be restored in down()
    }
};
