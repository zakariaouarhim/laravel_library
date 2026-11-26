<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreSeriesRequest;
use App\Http\Requests\Admin\UpdateSeriesRequest;
use App\Models\Book;
use App\Models\Series;
use App\Models\Author;
use App\Services\ImageService;
use App\Services\Seo\MetaBuilder;
use App\Services\Seo\SchemaBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminSeriesController extends Controller
{
    public function index()
    {
        $series = Series::withCount('books')
            ->with('author')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'    => Series::count(),
            'complete' => Series::where('is_complete', true)->count(),
            'ongoing'  => Series::where('is_complete', false)->count(),
        ];

        $authors = Author::where('status', 'active')->orderBy('name')->get();

        return view('Dashbord_Admin.series', compact('series', 'stats', 'authors'));
    }

    public function store(StoreSeriesRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = app(ImageService::class)
                ->processSeriesImage($request->file('cover_image'));
        }

        Series::create($validated);

        return back()->with('success', 'تم إنشاء السلسلة بنجاح.');
    }

    public function update(UpdateSeriesRequest $request, Series $series)
    {
        $validated = $request->validated();

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = app(ImageService::class)
                ->processSeriesImage($request->file('cover_image'), $series->cover_image);
        } else {
            unset($validated['cover_image']);
        }

        $series->update($validated);

        return response()->json(['success' => true, 'message' => 'تم تحديث السلسلة بنجاح.']);
    }

    public function destroy(Series $series)
    {
        if ($series->books()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذه السلسلة لأنها تحتوي على كتب. يرجى إزالة الكتب أولاً.',
            ], 422);
        }

        if ($series->cover_image) {
            Storage::disk('public')->delete($series->cover_image);
        }

        $series->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف السلسلة بنجاح.']);
    }

    /**
     * Public "all series in the store" index, optionally narrowed to one
     * language. The homepage's سلاسل عربية / سلاسل إنجليزية carousel titles link
     * here pre-filtered; visited bare it lists every series that has volumes.
     *
     * Ordered by volume count by default, which is the order
     * HomeCarouselService::series() uses, so the full listing opens on the same
     * series the strip was showing.
     */
    public function publicIndex(Request $request)
    {
        $request->validate([
            'q'        => 'nullable|string|max:191',
            // Lowercase: books.language is an enum of these exact values.
            'language' => 'nullable|in:' . implode(',', Book::LANGUAGES),
            'sort'     => 'nullable|in:name,books,newest',
        ]);

        // has('books') keeps series with no volumes out: there would be nothing
        // to click through to, and the homepage carousel excludes them too.
        $query = Series::has('books')
            ->with(['author', 'bundle'])
            ->withCount('books');

        if ($request->filled('q')) {
            // Escape the LIKE wildcards so a literal % or _ in a search box
            // cannot turn into a match-everything query.
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $request->input('q')) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhereHas('author', fn($a) => $a->where('name', 'like', $like));
            });
        }

        if ($request->filled('language')) {
            // whereHas over the volumes - a series whose books are mixed shows
            // under both languages, which is correct.
            $query->inLanguage($request->input('language'));
        }

        switch ($request->input('sort', 'books')) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            default:
                $query->orderByDesc('books_count');
        }

        // withQueryString keeps the active filters on pages 2+.
        $series = $query->paginate(24)->withQueryString();

        // Facet list, mirroring the publishers page's country dropdown: only
        // offer languages that some series actually has volumes in.
        $languages = Book::standardOnly()
            ->whereNotNull('series_id')
            ->whereNotNull('language')
            ->distinct()
            ->pluck('language')
            ->sort()
            ->values();

        $seo = app(MetaBuilder::class)->forStatic(
            'السلاسل - مكتبة الفقراء',
            'تصفح جميع السلاسل المتوفرة في مكتبة الفقراء، عربية وإنجليزية، واكتشف أجزاءها كاملة.',
            route('series.index')
        );
        if ($series->currentPage() > 1) {
            $seo['robots']    = 'noindex,follow';
            $seo['canonical'] = route('series.index');
        }

        return view('series-index', compact('series', 'languages', 'seo'));
    }

    public function publicShow(Series $series)
    {
        // Re-fetch with eager loading.
        $series = Series::with('author')->withCount('books')->whereKey($series->id)->firstOrFail();

        $books = Book::with(['primaryAuthor', 'bundles:id,title,price,image'])
            ->standardOnly()
            ->where('series_id', $series->id)
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->orderBy('volume_number')
            ->paginate(24);

        $bundles = Book::onlyBundles()
            ->where('series_id', $series->id)
            ->with(['items' => fn($q) => $q->orderBy('volume_number')])
            ->get();

        // Categories + language derived from the series' volumes so the series
        // page surfaces this context without admins re-entering it.
        $categories = $series->derivedCategories();

        $seo = app(MetaBuilder::class)->forSeries($series);
        if ($books->currentPage() > 1) {
            $seo['robots']    = 'noindex,follow';
            $seo['canonical'] = route('series.show', $series);
        }

        $schemaBuilder = app(SchemaBuilder::class);
        $trail = [
            ['label' => 'الرئيسية', 'url' => url('/')],
            ['label' => $series->name],
        ];
        $schemas = [
            'series'      => $schemaBuilder->forSeries($series, collect($books->items())),
            'breadcrumbs' => $schemaBuilder->forBreadcrumbs($trail),
        ];

        return view('series', compact('series', 'books', 'bundles', 'categories', 'seo', 'schemas'));
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $series = Series::where('name', 'like', "%{$q}%")
            ->with('author')
            ->limit(20)
            ->get(['id', 'name', 'author_id', 'total_volumes', 'is_complete']);

        return response()->json($series);
    }
}
