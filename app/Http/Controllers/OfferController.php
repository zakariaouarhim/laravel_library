<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Offer;
use App\Services\Seo\MetaBuilder;

class OfferController extends Controller
{
    /**
     * Public offers hub: active library promos + all discounted books.
     */
    public function index()
    {
        $offers = Offer::active()
            ->withCount('books')
            ->orderByRaw('ends_at IS NULL, ends_at ASC')
            ->get();

        $discountedBooks = Book::standardOnly()
            ->where('type', 'book')
            ->where('status', 'active')
            ->where('discount', '>', 0)
            ->with(['primaryAuthor', 'authors', 'bundles:id,title,price,image'])
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->orderByDesc('discount')
            ->paginate(12);

        $seo = app(MetaBuilder::class)->forStatic(
            'عروض مكتبة الفقراء',
            'اكتشف أحدث عروض وتخفيضات مكتبة الفقراء على الكتب — وفّر أكثر مع عروض المكتبة الحصرية.',
            route('offers.index')
        );

        return view('offers.index', compact('offers', 'discountedBooks', 'seo'));
    }

    /**
     * Public offer detail: shows the hand-picked eligible books (read-only in Phase 1).
     */
    public function show(Offer $offer)
    {
        // 404 unless the offer is currently active (mirrors AuthorController@publicShow).
        $offer = Offer::active()->whereKey($offer->id)->firstOrFail();

        // Series/bundle units (picked whole, counted by book count).
        $units = $offer->resolveUnits();

        // Loose eligible books (hand-picked ∪ price rule, minus excluded & unit members).
        // Paginated; subsequent pages load via AJAX (offer.books) so the in-page
        // "pick N" selection isn't lost to a full reload.
        $eligibleBooks = $offer->eligibleBooksQuery()->paginate(self::ELIGIBLE_PER_PAGE);

        // Filter facets (language + category) present among the loose eligible books.
        $looseIds  = $offer->eligibleBooksQuery()->pluck('id');
        $languages = Book::whereIn('id', $looseIds)->distinct()->pluck('language')->filter()->values();
        $filterCategories = Category::whereIn('id',
            Book::whereIn('id', $looseIds)->whereNotNull('category_id')->distinct()->pluck('category_id')
        )->orderBy('name')->get(['id', 'name']);

        $seo = app(MetaBuilder::class)->forStatic(
            $offer->meta_title ?: $offer->title . ' - عروض مكتبة الفقراء',
            $offer->meta_description ?: ($offer->description
                ? \Illuminate\Support\Str::limit(strip_tags($offer->description), 155)
                : "اختر {$offer->quantity} كتب من هذا العرض بسعر {$offer->fixed_price} درهم في مكتبة الفقراء."),
            route('offer.show', $offer)
        );

        return view('offers.show', compact('offer', 'eligibleBooks', 'units', 'languages', 'filterCategories', 'seo'));
    }

    /** Number of eligible books per page on the offer detail screen. */
    private const ELIGIBLE_PER_PAGE = 24;

    /**
     * AJAX: a page of the offer's eligible books, for "load more" on the detail page.
     */
    public function books(Offer $offer, \Illuminate\Http\Request $request)
    {
        $offer = Offer::active()->whereKey($offer->id)->firstOrFail();

        $data = $request->validate([
            'q'        => 'nullable|string|max:191',
            'page'     => 'nullable|integer|min:1',
            'language' => 'nullable|string|max:50',
            'category' => 'nullable|integer',
        ]);

        $query = $offer->eligibleBooksQuery();

        if (!empty($data['q'])) {
            $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $data['q']);
            $like = '%' . $term . '%';
            $query->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)
                  ->orWhereHas('primaryAuthor', fn($a) => $a->where('name', 'like', $like))
                  ->orWhereHas('authors', fn($a) => $a->where('authors.name', 'like', $like));
            });
        }
        if (!empty($data['language'])) {
            $query->where('language', $data['language']);
        }
        if (!empty($data['category'])) {
            $query->where('category_id', $data['category']);
        }

        $page = $query->paginate(self::ELIGIBLE_PER_PAGE);

        return response()->json([
            'books' => collect($page->items())->map(fn(Book $b) => [
                'id'       => $b->id,
                'title'    => $b->title,
                'author'   => optional($b->primaryAuthor)->name,
                'image'    => asset($b->image),
                'in_stock' => (int) $b->quantity > 0,
            ])->values(),
            'has_more'  => $page->hasMorePages(),
            'next_page' => $page->currentPage() + 1,
        ]);
    }
}
