<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\StoreContactRequest;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Models\Series;
use App\Models\ContactMessage;
use App\Mail\ContactAutoReply;
use App\Services\Seo\MetaBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    public function __construct(private MetaBuilder $meta) {}

    public function about()
    {
        $stats = Cache::remember('about_stats', 3600, function () {
            return [
                'books' => Book::where('type', 'book')->count(),
                'authors' => Author::active()->count(),
                'categories' => Category::count(),
            ];
        });

        $seo = $this->meta->forStatic(
            'من نحن - مكتبة الفقراء',
            'تعرف على مكتبة الفقراء، رسالتنا وقيمنا في نشر المعرفة وتوفير الكتب بأسعار مناسبة للجميع.',
            route('about.page')
        );

        $faqs = \App\Models\Faq::active()->ordered()->get();
        $schemas = [];
        $faqSchema = app(\App\Services\Seo\SchemaBuilder::class)->forFaqPage($faqs);
        if (!empty($faqSchema)) {
            $schemas['faq'] = $faqSchema;
        }

        return view('about', compact('stats', 'seo', 'faqs', 'schemas'));
    }

    public function contact()
    {
        $seo = $this->meta->forStatic(
            'اتصل بنا - مكتبة الفقراء',
            'تواصل مع مكتبة الفقراء. نحن هنا لمساعدتك والإجابة على جميع استفساراتك.',
            route('contact.page')
        );

        $schemas = [];
        $bookStore = app(\App\Services\Seo\SchemaBuilder::class)->forBookStore();
        if (!empty($bookStore)) {
            $schemas['bookstore'] = $bookStore;
        }

        return view('contact', compact('seo', 'schemas'));
    }

    public function storeContact(StoreContactRequest $request)
    {
        $validated = $request->validated();

        ContactMessage::create($validated);

        // Auto-reply confirmation email, QUEUED not sent.
        //
        // This was ->send(), i.e. a synchronous SMTP handshake inside the
        // request, to an address the submitter supplied. The 9,437 bot rows
        // purged on 2026-11-26 therefore each fired an outbound mail to a
        // bot-chosen address while holding a php-fpm worker open -- backscatter
        // that costs sender reputation and so threatens delivery of the mail
        // that actually matters (order confirmations). A queue worker already
        // runs under supervisor (QUEUE_CONNECTION=database), so handing this to
        // it both frees the request and takes the mail off the hot path.
        try {
            Mail::to($validated['email'])->queue(new ContactAutoReply($validated['name'], $validated['subject']));
        } catch (\Exception $e) {
            \Log::error('Failed to queue contact auto-reply:', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.');
    }

    public function privacy()
    {
        $seo = $this->meta->forStatic(
            'سياسة الخصوصية - مكتبة الفقراء',
            'سياسة الخصوصية لمكتبة الفقراء — كيف نجمع بياناتك ونحميها ونستخدمها.',
            route('privacy.page')
        );

        return view('privacy', compact('seo'));
    }

    public function terms()
    {
        $seo = $this->meta->forStatic(
            'الشروط والأحكام - مكتبة الفقراء',
            'الشروط والأحكام لاستخدام متجر مكتبة الفقراء — الطلبات، الشحن، الإرجاع، وحقوقك.',
            route('terms.page')
        );

        return view('terms', compact('seo'));
    }

    public function sitemap()
    {
        // Key is versioned because the cached array's SHAPE changed when series
        // were added: an entry written by the previous deploy has no 'series'
        // key, and nothing invalidates this cache, so reusing the old key would
        // have thrown "Undefined variable $series" in the view for up to an hour
        // after release. Bump the suffix whenever a key is added or removed here.
        $sitemapData = Cache::remember('sitemap_data_v2', 3600, function () {
            // slug is required — getRouteKey() returns slug; without it the URLs fall back to ID.
            return [
                'books' => Book::where('type', 'book')
                    ->select('id', 'slug', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->get(),
                'accessories' => Book::where('type', 'accessory')
                    ->select('id', 'slug', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->get(),
                'authors' => Author::active()
                    ->select('id', 'slug', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->get(),
                'categories' => Category::select('id', 'slug', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->get(),
                'publishers' => PublishingHouse::active()
                    ->select('id', 'slug', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->get(),
                // has('books') matches the public index, which hides series with
                // no volumes - no point advertising a page with nothing on it.
                'series' => Series::has('books')
                    ->select('id', 'slug', 'updated_at')
                    ->orderBy('updated_at', 'desc')
                    ->get(),
            ];
        });

        return response()
            ->view('sitemap', $sitemapData)
            ->header('Content-Type', 'text/xml');
    }
}
