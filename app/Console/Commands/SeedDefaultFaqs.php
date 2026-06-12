<?php

namespace App\Console\Commands;

use App\Models\Faq;
use Illuminate\Console\Command;

/**
 * Idempotent seeder for baseline FAQs. Skips if any FAQ already exists —
 * admin edits via the admin UI take precedence.
 */
class SeedDefaultFaqs extends Command
{
    protected $signature = 'faqs:seed-defaults';
    protected $description = 'Seed 5 baseline FAQs (shipping, returns, tracking, formats, contact)';

    public function handle(): int
    {
        if (Faq::count() > 0) {
            $this->info('FAQs already exist. Nothing to seed.');
            return self::SUCCESS;
        }

        $defaults = [
            [
                'question' => 'كم يستغرق الشحن داخل المغرب؟',
                'answer'   => "نشحن الطلبات عادةً خلال 24-48 ساعة من تأكيد الطلب. يستغرق الوصول من 2 إلى 5 أيام عمل بحسب المدينة.\n\nيمكنك تتبع طلبك في أي وقت من خلال حسابك.",
            ],
            [
                'question' => 'هل يمكنني إرجاع كتاب لا يعجبني؟',
                'answer'   => "نعم، يحق لك إرجاع الكتاب خلال 7 أيام من استلامه شرط أن يكون بحالته الأصلية.\n\nسجّل طلب الإرجاع من حسابك ثم سننسق معك آلية الاستلام.",
            ],
            [
                'question' => 'كيف أتتبع طلبي؟',
                'answer'   => 'بعد تأكيد الطلب، نرسل لك تأكيداً برقم الطلب. يمكنك متابعة جميع طلباتك من قسم «طلباتي» في حسابك الشخصي.',
            ],
            [
                'question' => 'هل تتوفر الكتب بصيغة PDF؟',
                'answer'   => 'نحن مكتبة كتب ورقية. لا نبيع نسخاً رقمية أو ملفات PDF حالياً. جميع الكتب يتم شحنها بنسختها الأصلية.',
            ],
            [
                'question' => 'كيف أتواصل مع خدمة العملاء؟',
                'answer'   => "يمكنك التواصل معنا عبر صفحة «اتصل بنا» أو عبر واتساب على الرقم الموجود في الصفحة.\n\nنرد على الاستفسارات عادةً خلال ساعات العمل.",
            ],
        ];

        foreach ($defaults as $i => $row) {
            Faq::create([
                'question'      => $row['question'],
                'answer'        => $row['answer'],
                'display_order' => ($i + 1) * 10,
                'is_active'     => true,
            ]);
        }

        $this->info('Seeded ' . count($defaults) . ' baseline FAQs.');
        return self::SUCCESS;
    }
}
