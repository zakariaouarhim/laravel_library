<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixForeignKeyUserTableReferences extends Migration
{
    /**
     * Fix FK constraints that incorrectly reference 'users' or 'usermodel'
     * instead of the actual 'user' table.
     */
    public function up()
    {
        $tables = ['reviews', 'quote_likes', 'reading_goals'];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            // Ensure InnoDB engine (MyISAM silently ignores FK constraints)
            $engine = DB::select(
                "SELECT ENGINE FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$tableName]
            );
            if (!empty($engine) && $engine[0]->ENGINE !== 'InnoDB') {
                DB::statement("ALTER TABLE {$tableName} ENGINE = InnoDB");
            }

            // Check if a FK constraint on user_id already exists
            $existingFk = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME LIKE '%user_id%'",
                [$tableName]
            );

            // Drop existing FK if found (it may point to wrong table)
            if (!empty($existingFk)) {
                $fkName = $existingFk[0]->CONSTRAINT_NAME;
                DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY {$fkName}");
            }

            // Add correct FK pointing to 'user' table
            $fkName = $tableName . '_user_id_foreign';
            DB::statement("ALTER TABLE {$tableName} ADD CONSTRAINT {$fkName} FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE");
        }
    }

    public function down()
    {
        // Reverting would restore the broken references — not useful
    }
}
