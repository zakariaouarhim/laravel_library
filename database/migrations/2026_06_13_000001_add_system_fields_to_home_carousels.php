<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Built-in homepage carousels seeded as "system" rows so the admin can edit
     * title/limit/order/visibility/availability. Resolution logic stays in code,
     * keyed by system_key. Order/titles/limits mirror the previous hardcoded homepage.
     */
    private array $systemCarousels = [
        ['system_key' => 'recommended',      'title' => 'موصى لك',              'sort_order' => 10,  'book_limit' => 12],
        ['system_key' => 'from_follows',     'title' => 'جديد من متابعاتك',     'sort_order' => 20,  'book_limit' => 15],
        ['system_key' => 'new_arrivals',     'title' => 'وصل حديثا',            'sort_order' => 30,  'book_limit' => 20],
        ['system_key' => 'categories_strip', 'title' => 'أشعر الآن وكأنني أريد...', 'sort_order' => 40,  'book_limit' => 12],
        ['system_key' => 'popular',          'title' => 'الأكثر مبيعا',         'sort_order' => 50,  'book_limit' => 10],
        ['system_key' => 'arabic_series',    'title' => 'سلاسل عربية',          'sort_order' => 60,  'book_limit' => 10],
        ['system_key' => 'accessories',      'title' => 'إكسسوارات القراءة',    'sort_order' => 70,  'book_limit' => 10],
        ['system_key' => 'english_books',    'title' => 'كتب بالإنجليزية',      'sort_order' => 80,  'book_limit' => 10],
        ['system_key' => 'english_series',   'title' => 'سلاسل إنجليزية',       'sort_order' => 90,  'book_limit' => 10],
        ['system_key' => 'french_books',     'title' => 'كتب بالفرنسية',        'sort_order' => 100, 'book_limit' => 10],
        ['system_key' => 'recently_viewed',  'title' => 'شاهدت مؤخراً',         'sort_order' => 110, 'book_limit' => 10],
    ];

    public function up(): void
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->string('system_key')->nullable()->unique()->after('id');
            $table->boolean('show_unavailable')->default(true)->after('is_active');
        });

        // Keep existing custom carousels in their current homepage spot (between
        // french_books=100 and recently_viewed=110) instead of jumping to the top.
        DB::table('home_carousels')->whereNull('system_key')->where('sort_order', 0)->update(['sort_order' => 105]);

        // Idempotent seed of the built-ins.
        foreach ($this->systemCarousels as $row) {
            DB::table('home_carousels')->updateOrInsert(
                ['system_key' => $row['system_key']],
                [
                    'title'            => $row['title'],
                    'source_type'      => 'categories', // unused for system rows; column is NOT NULL
                    'book_limit'       => $row['book_limit'],
                    'sort_order'       => $row['sort_order'],
                    'is_active'        => true,
                    'show_unavailable' => true,
                    'updated_at'       => now(),
                    'created_at'       => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('home_carousels')->whereNotNull('system_key')->delete();

        Schema::table('home_carousels', function (Blueprint $table) {
            $table->dropUnique(['system_key']);
            $table->dropColumn(['system_key', 'show_unavailable']);
        });
    }
};
