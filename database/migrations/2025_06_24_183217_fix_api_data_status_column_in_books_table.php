<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add a temporary column with the new enum values
        Schema::table('books', function (Blueprint $table) {
            $table->enum('api_data_status_temp', ['pending', 'processing', 'enriched', 'failed'])
                  ->default('pending')
                  ->after('api_data_status');
        });

        // Step 2: Copy data from old column to new column
        DB::statement("UPDATE books SET api_data_status_temp = api_data_status");

        // Step 3: Drop the old column
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('api_data_status');
        });

        // Step 4: Rename the temporary column to the original name
        Schema::table('books', function (Blueprint $table) {
            $table->renameColumn('api_data_status_temp', 'api_data_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Add temporary column with original enum values
        Schema::table('books', function (Blueprint $table) {
            $table->enum('api_data_status_temp', ['pending', 'enriched', 'failed'])
                  ->default('pending')
                  ->after('api_data_status');
        });

        // Step 2: Copy data (filter out 'processing' values)
        DB::statement("UPDATE books SET api_data_status_temp = CASE 
            WHEN api_data_status = 'processing' THEN 'pending' 
            ELSE api_data_status 
        END");

        // Step 3: Drop the current column
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn('api_data_status');
        });

        // Step 4: Rename back
        Schema::table('books', function (Blueprint $table) {
            $table->renameColumn('api_data_status_temp', 'api_data_status');
        });
    }
};