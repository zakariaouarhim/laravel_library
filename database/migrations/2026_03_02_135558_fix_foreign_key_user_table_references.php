<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixForeignKeyUserTableReferences extends Migration
{
    /**
     * Fix FK constraints that incorrectly reference 'users' or 'usermodel'
     * instead of the actual 'user' table.
     */
    public function up()
    {
        // Fix reviews table: user_id FK references 'users' but should be 'user'
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // FK may not exist or have different name
                }
            });
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
            });
        }

        // Fix quote_likes table: user_id FK references 'users' but should be 'user'
        if (Schema::hasTable('quote_likes')) {
            Schema::table('quote_likes', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // FK may not exist or have different name
                }
            });
            Schema::table('quote_likes', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
            });
        }

        // Fix reading_goals table: user_id FK references 'usermodel' but should be 'user'
        if (Schema::hasTable('reading_goals')) {
            Schema::table('reading_goals', function (Blueprint $table) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // FK may not exist or have different name
                }
            });
            Schema::table('reading_goals', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        // Reverting would restore the broken references — not useful
    }
}
