<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shop\StoreContactRequest;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Models\ContactMessage;
use App\Mail\ContactAutoReply;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    public function about()
    {
        $stats = Cache::remember('about_stats', 3600, function () {
            return [
                'books' => Book::where('type', 'book')->count(),
                'authors' => Author::active()->count(),
                'categories' => Category::count(),
            ];
        });

        return view('about', compact('stats'));
    }

    public function contact()
    {
        return view('contact');
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
        return view('privacy');
    }

    public function terms()
    {
        return view('terms');
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
