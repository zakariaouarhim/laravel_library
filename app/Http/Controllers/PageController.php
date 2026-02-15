<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Models\PublishingHouse;
use App\Models\ContactMessage;

class PageController extends Controller
{
    public function about()
    {
        $stats = [
            'books' => Book::where('type', 'book')->count(),
            'authors' => Author::active()->count(),
            'categories' => Category::count(),
        ];

        return view('about', compact('stats'));
    }

    public function contact()
    {
        return view('contact');
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        ContactMessage::create($validated);

        return back()->with('success', 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.');
    }

    public function sitemap()
    {
        $books = Book::where('type', 'book')
            ->select('id', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $accessories = Book::where('type', 'accessory')
            ->select('id', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $authors = Author::active()
            ->select('id', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $categories = Category::select('id', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $publishers = PublishingHouse::active()
            ->select('id', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()
            ->view('sitemap', compact('books', 'accessories', 'authors', 'categories', 'publishers'))
            ->header('Content-Type', 'text/xml');
    }
}
