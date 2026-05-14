<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_books', function (Blueprint $t) {
            $t->foreignId('author_id')->nullable()->after('author_name')->constrained('authors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pending_books', function (Blueprint $t) {
            $t->dropForeign(['author_id']);
            $t->dropColumn('author_id');
        });
    }
};
