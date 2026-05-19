<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publishing_houses', function (Blueprint $t) {
            $t->string('slug', 255)->nullable()->after('name');
            $t->string('meta_title', 70)->nullable()->after('slug');
            $t->string('meta_description', 160)->nullable()->after('meta_title');
            $t->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('publishing_houses', function (Blueprint $t) {
            $t->dropUnique(['slug']);
            $t->dropColumn(['slug', 'meta_title', 'meta_description']);
        });
    }
};
