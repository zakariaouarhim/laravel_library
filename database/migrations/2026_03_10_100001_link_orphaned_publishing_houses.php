<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Link books that have a Publishing_House string but no publishing_house_id FK
        $orphaned = DB::table('books')
            ->whereNotNull('Publishing_House')
            ->where('Publishing_House', '!=', '')
            ->whereNull('publishing_house_id')
            ->get(['id', 'Publishing_House']);

        foreach ($orphaned as $book) {
            $ph = DB::table('publishing_houses')
                ->where('name', $book->Publishing_House)
                ->first();

            if (!$ph) {
                $phId = DB::table('publishing_houses')->insertGetId([
                    'name'       => $book->Publishing_House,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $phId = $ph->id;
            }

            DB::table('books')
                ->where('id', $book->id)
                ->update(['publishing_house_id' => $phId]);
        }
    }

    public function down(): void
    {
        // No reversal needed — the FK data is correct either way
    }
};
