<?php

namespace Database\Seeders;

use App\Models\Series;
use App\Models\Author;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $nagib = Author::firstOrCreate(
            ['name' => 'نجيب محفوظ'],
            ['status' => 'active']
        );

        $knowledge = Author::firstOrCreate(
            ['name' => 'المجلس الوطني للثقافة والفنون والآداب'],
            ['status' => 'active']
        );

        Series::firstOrCreate(['name' => 'الثلاثية'], [
            'description' => 'ثلاثية القاهرة لنجيب محفوظ: بين القصرين، قصر الشوق، السكرية',
            'author_id'     => $nagib->id,
            'total_volumes' => 3,
            'is_complete'   => true,
        ]);

        Series::firstOrCreate(['name' => 'عالم المعرفة'], [
            'description' => 'سلسلة كتب ثقافية شهرية يصدرها المجلس الوطني للثقافة والفنون والآداب في الكويت',
            'author_id'     => $knowledge->id,
            'total_volumes' => null,
            'is_complete'   => false,
        ]);

        Series::firstOrCreate(['name' => 'هاري بوتر'], [
            'description' => 'سلسلة روايات الخيال للكاتبة ج. ك. رولينغ — النسخة العربية',
            'author_id'     => null,
            'total_volumes' => 7,
            'is_complete'   => true,
        ]);
    }
}
