<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Flip all pending reviews to approved now that we trust user-submitted
        // content by default (post-moderation model).
        DB::table('reviews')->where('status', 'pending')->update(['status' => 'approved']);
    }

    public function down(): void
    {
        // No-op: we cannot distinguish reviews that were originally pending
        // from ones that were already approved before this migration.
    }
};
