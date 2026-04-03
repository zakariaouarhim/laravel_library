<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InsertCategories extends Command
{
    protected $signature = 'categories:insert';
    protected $description = 'Insert new book categories in Arabic, English, and French';

    public function handle()
    {
        $now = now();

        // Arabic-only categories (no parent)
        $arabicOnly = [
            'أدب عربي', 'شعر عربي', 'تاريخ عربي',
            'فقه', 'تاريخ إسلامي', 'مالية إسلامية', 'قصص الأنبياء', 'أدعية وأذكار', 'أخلاق إسلامية',
        ];

        $inserted = 0;
        foreach ($arabicOnly as $name) {
            if (!DB::table('categories')->where('name', $name)->exists()) {
                DB::table('categories')->insert(['name' => $name, 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now]);
                $inserted++;
            }
        }
        $this->info("Arabic-only: {$inserted} inserted");

        // Universal categories: [english, arabic, french]
        $universal = [
            ['Science Fiction', 'خيال علمي', 'Science-fiction'],
            ['Dystopia', 'ديستوبيا', 'Dystopie'],
            ['Fantasy', 'فانتازيا', 'Fantaisie'],
            ['Thriller', 'إثارة', 'Thriller'],
            ['Detective Fiction', 'روايات بوليسية', 'Roman policier'],
            ['Psychological Thriller', 'إثارة نفسية', 'Thriller psychologique'],
            ['Drama', 'دراما', 'Drame'],
            ['Satire', 'أدب ساخر', 'Satire'],
            ['Magical Realism', 'واقعية سحرية', 'Réalisme magique'],
            ['Gothic Fiction', 'أدب قوطي', 'Fiction gothique'],
            ['War Fiction', 'روايات حروب', 'Roman de guerre'],
            ['Mythology', 'أساطير وميثولوجيا', 'Mythologie'],
            ['Young Adult', 'أدب الشباب', 'Jeune adulte'],
            ['Graphic Novels', 'روايات مصورة', 'Romans graphiques'],
            ['Epic Fiction', 'ملاحم أدبية', 'Fiction épique'],
            ['Espionage Fiction', 'روايات تجسس', "Roman d'espionnage"],
            ['Apocalyptic Fiction', 'أدب ما بعد الكارثة', 'Fiction apocalyptique'],
            ['Dark Academia', 'أكاديميا مظلمة', 'Dark Academia'],
            ['Fairy Tales', 'حكايات وأساطير شعبية', 'Contes de fées'],
            ['Tragic Fiction', 'تراجيديا', 'Tragédie'],
            ['World Literature', 'أدب عالمي', 'Littérature mondiale'],
            ['Literary Criticism', 'نقد أدبي', 'Critique littéraire'],
            ['Linguistics', 'لغويات', 'Linguistique'],
            ['Translation Studies', 'دراسات الترجمة', 'Études de traduction'],
            ['Creative Writing', 'الكتابة الإبداعية', 'Écriture créative'],
            ['Essays', 'مقالات', 'Essais'],
            ['Entrepreneurship', 'ريادة الأعمال', 'Entrepreneuriat'],
            ['Leadership', 'قيادة', 'Leadership'],
            ['Personal Finance', 'التمويل الشخصي', 'Finances personnelles'],
            ['Negotiation', 'تفاوض', 'Négociation'],
            ['Startups', 'شركات ناشئة', 'Startups'],
            ['Real Estate', 'عقارات', 'Immobilier'],
            ['Productivity', 'إنتاجية', 'Productivité'],
            ['Public Speaking', 'فن الخطابة', 'Art oratoire'],
            ['E-commerce', 'تجارة إلكترونية', 'Commerce électronique'],
            ['Computer Science', 'علوم الحاسوب', 'Informatique'],
            ['Programming', 'برمجة', 'Programmation'],
            ['Artificial Intelligence', 'ذكاء اصطناعي', 'Intelligence artificielle'],
            ['Cybersecurity', 'أمن سيبراني', 'Cybersécurité'],
            ['Medicine', 'طب', 'Médecine'],
            ['Engineering', 'هندسة', 'Ingénierie'],
            ['Astronomy', 'فلك وفضاء', 'Astronomie'],
            ['Chemistry', 'كيمياء', 'Chimie'],
            ['Biology', 'أحياء', 'Biologie'],
            ['Geography', 'جغرافيا', 'Géographie'],
            ['Mindfulness', 'وعي وتأمل', 'Pleine conscience'],
            ['Motivation', 'تحفيز', 'Motivation'],
            ['Emotional Intelligence', 'ذكاء عاطفي', 'Intelligence émotionnelle'],
            ['Habits & Routines', 'عادات وروتين', 'Habitudes et routines'],
            ['Nutrition', 'تغذية', 'Nutrition'],
            ['Fitness', 'لياقة بدنية', 'Forme physique'],
            ['Alternative Medicine', 'طب بديل', 'Médecine alternative'],
            ['Yoga & Meditation', 'يوغا وتأمل', 'Yoga et méditation'],
            ['Feminism', 'نسوية', 'Féminisme'],
            ['Cultural Studies', 'دراسات ثقافية', 'Études culturelles'],
            ['Anthropology', 'أنثروبولوجيا', 'Anthropologie'],
            ['True Crime', 'جرائم حقيقية', 'Crimes réels'],
            ['Journalism', 'صحافة وإعلام', 'Journalisme'],
            ['Human Rights', 'حقوق الإنسان', "Droits de l'homme"],
            ['Immigration', 'هجرة ولجوء', 'Immigration'],
            ['Urban Studies', 'دراسات حضرية', 'Études urbaines'],
            ['Photography', 'تصوير', 'Photographie'],
            ['Architecture', 'عمارة', 'Architecture'],
            ['Music', 'موسيقى', 'Musique'],
            ['Cinema & Film', 'سينما وأفلام', 'Cinéma et films'],
            ['Cooking', 'طبخ', 'Cuisine'],
            ['Travel', 'سفر ورحلات', 'Voyages'],
            ['Fashion & Design', 'أزياء وتصميم', 'Mode et design'],
            ['Gardening', 'بستنة وزراعة', 'Jardinage'],
            ['Sports', 'رياضة', 'Sports'],
            ['Ancient History', 'تاريخ قديم', 'Histoire ancienne'],
            ['Modern History', 'تاريخ حديث', 'Histoire moderne'],
            ['Autobiography', 'سيرة ذاتية', 'Autobiographie'],
            ['Political Memoirs', 'مذكرات سياسية', 'Mémoires politiques'],
            ['War & Military History', 'تاريخ حروب', 'Histoire militaire'],
            ['Russian Literature', 'أدب روسي', 'Littérature russe'],
            ['Japanese Literature', 'أدب ياباني', 'Littérature japonaise'],
            ['French Literature', 'أدب فرنسي', 'Littérature française'],
            ['English Literature', 'أدب إنجليزي', 'Littérature anglaise'],
            ['American Literature', 'أدب أمريكي', 'Littérature américaine'],
            ['German Literature', 'أدب ألماني', 'Littérature allemande'],
            ['Spanish Literature', 'أدب إسباني', 'Littérature espagnole'],
            ['Latin American Literature', 'أدب أمريكا اللاتينية', 'Littérature latino-américaine'],
            ['Italian Literature', 'أدب إيطالي', 'Littérature italienne'],
            ['Chinese Literature', 'أدب صيني', 'Littérature chinoise'],
            ['Indian Literature', 'أدب هندي', 'Littérature indienne'],
            ['Persian Literature', 'أدب فارسي', 'Littérature persane'],
            ['Turkish Literature', 'أدب تركي', 'Littérature turque'],
            ['African Literature', 'أدب أفريقي', 'Littérature africaine'],
            ['Korean Literature', 'أدب كوري', 'Littérature coréenne'],
            ['Greek Literature', 'أدب يوناني', 'Littérature grecque'],
            ['Scandinavian Literature', 'أدب إسكندنافي', 'Littérature scandinave'],
            ['Portuguese Literature', 'أدب برتغالي', 'Littérature portugaise'],
            ['Moroccan Literature', 'أدب مغربي', 'Littérature marocaine'],
        ];

        $enCount = 0; $arCount = 0; $frCount = 0;
        foreach ($universal as $cat) {
            // Arabic (no parent)
            if (!DB::table('categories')->where('name', $cat[1])->exists()) {
                DB::table('categories')->insert(['name' => $cat[1], 'parent_id' => null, 'created_at' => $now, 'updated_at' => $now]);
                $arCount++;
            }
            // English (parent: English Books = 9)
            if (!DB::table('categories')->where('name', $cat[0])->where('parent_id', 9)->exists()) {
                DB::table('categories')->insert(['name' => $cat[0], 'parent_id' => 9, 'created_at' => $now, 'updated_at' => $now]);
                $enCount++;
            }
            // French (parent: French = 79)
            if (!DB::table('categories')->where('name', $cat[2])->where('parent_id', 79)->exists()) {
                DB::table('categories')->insert(['name' => $cat[2], 'parent_id' => 79, 'created_at' => $now, 'updated_at' => $now]);
                $frCount++;
            }
        }

        $this->info("Arabic universal: {$arCount}");
        $this->info("English: {$enCount}");
        $this->info("French: {$frCount}");
        $this->info("Total inserted: " . ($inserted + $arCount + $enCount + $frCount));
        $this->info("Grand total categories: " . DB::table('categories')->count());
    }
}
