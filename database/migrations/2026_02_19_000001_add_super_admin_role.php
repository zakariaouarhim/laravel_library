<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `user` MODIFY `role` ENUM('user','admin','super_admin') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        // Demote any super_admin to admin before reverting the enum
        DB::table('user')->where('role', 'super_admin')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE `user` MODIFY `role` ENUM('user','admin') NOT NULL DEFAULT 'user'");
    }
};
