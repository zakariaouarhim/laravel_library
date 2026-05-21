<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\StoreContactRequest;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\PublishingHouse;
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

        return view('about', compact('stats', 'seo'));
    }

    public function contact()
    {
        $seo = $this->meta->forStatic(
            'اتصل بنا - مكتبة الفقراء',
            'تواصل مع مكتبة الفقراء. نحن هنا لمساعدتك والإجابة على جميع استفساراتك.',
            route('contact.page')
        );

        return view('contact', compact('seo'));
    }

    public function storeContact(StoreContactRequest $request)
    {
        $validated = $request->validated();

        ContactMessage::create($validated);

        // Send auto-reply confirmation email
        try {
            Mail::to($validated['email'])->send(new ContactAutoReply($validated['name'], $validated['subject']));
        } catch (\Exception $e) {
            \Log::error('Failed to send contact auto-reply:', ['error' => $e->getMessage()]);
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
        $sitemapData = Cache::remember('sitemap_data', 3600, function () {
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
            ];
        });

        return response()
            ->view('sitemap', $sitemapData)
            ->header('Content-Type', 'text/xml');
    }
}
