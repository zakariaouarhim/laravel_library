<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SimplifyCheckoutDetailsForm extends Migration
{
    public function up()
    {
        // Step 1: Add full_name column and merge existing data
        Schema::table('checkout_details', function (Blueprint $table) {
            $table->string('full_name')->after('order_id')->default('');
        });

        // Merge first_name + last_name into full_name for existing records
        DB::table('checkout_details')->update([
            'full_name' => DB::raw("CONCAT(first_name, ' ', last_name)")
        ]);

        // Step 2: Drop old columns, make email nullable
        Schema::table('checkout_details', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'zip_code']);
            $table->string('email')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('checkout_details', function (Blueprint $table) {
            $table->string('first_name')->after('order_id')->default('');
            $table->string('last_name')->after('first_name')->default('');
            $table->string('zip_code')->after('city')->default('');
            $table->string('email')->nullable(false)->change();
        });

        // Split full_name back (best effort: first word = first_name, rest = last_name)
        DB::table('checkout_details')->update([
            'first_name' => DB::raw("SUBSTRING_INDEX(full_name, ' ', 1)"),
            'last_name'  => DB::raw("TRIM(SUBSTR(full_name, LOCATE(' ', full_name) + 1))")
        ]);

        Schema::table('checkout_details', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
}
