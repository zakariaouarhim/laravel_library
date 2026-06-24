<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreOfferRequest;
use App\Http\Requests\Admin\UpdateOfferRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Series;
use App\Services\BookAdminService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AdminOfferController extends Controller
{
    public function __construct(private BookAdminService $adminService) {}

    public function index(Request $request)
    {
        $query = Offer::withCount('books')
            ->with(['books' => fn($q) => $q->select('books.id', 'title', 'author_id')->with('primaryAuthor:id,name')]);

        if ($search = $request->input('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->where('title', 'like', '%' . $search . '%');
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $offers = $query->latest()->paginate(20)->withQueryString();

        // Parent categories with their children, for the "browse by category" picker.
        $categories = Category::whereNull('parent_id')
            ->with(['children' => fn($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        // Series and bundles, added to an offer as whole units (with book counts for labels).
        $series  = Series::withCount('books')->orderBy('name')->get(['id', 'name']);
        $bundles = Book::where('product_type', 'bundle')->withCount('items')->orderBy('title')->get(['id', 'title']);

        return view('Dashbord_Admin.offers', compact('offers', 'categories', 'series', 'bundles'));
    }

    /**
     * Lightweight book list for the offer book-picker's "browse" modes.
     * Source = category | series | bundle; in every case it returns the books to
     * bulk-add to the offer's eligible books (the offer stays "pick N books").
     *  - category: books whose primary category_id matches OR are tagged via the
     *    pivot; parent categories optionally include one level of children.
     *  - series:   the series' member books (hasMany via series_id).
     *  - bundle:   the bundle product's member books (bundle_items pivot).
     */
    public function pickerBooks(Request $request)
    {
        $data = $request->validate([
            'source'           => 'required|in:category,series,bundle,price',
            'id'               => 'required_unless:source,price|nullable|integer',
            'include_children' => 'nullable|boolean',
            'q'                => 'nullable|string|max:191',
            'min'              => 'nullable|numeric|min:0',
            'max'              => 'nullable|numeric|min:0',
        ]);

        $books = match ($data['source']) {
            'series' => $this->seriesBooks((int) $data['id']),
            'bundle' => $this->bundleBooks((int) $data['id']),
            'price'  => $this->priceRangeBooks($data['min'] ?? null, $data['max'] ?? null, $data['q'] ?? null),
            default  => $this->categoryBooks((int) $data['id'], $request->boolean('include_children')),
        };

        // In-list title filter, applied uniformly across sources.
        if (!empty($data['q'])) {
            $needle = mb_strtolower($data['q']);
            $books = $books->filter(fn(Book $b) => mb_strpos(mb_strtolower((string) $b->title), $needle) !== false);
        }

        return response()->json([
            'books' => $books->take(300)->map(fn(Book $b) => [
                'id'       => $b->id,
                'title'    => $b->title,
                'author'   => optional($b->primaryAuthor)->name,
                'price'    => $b->price,
                'image'    => $b->image,
                'in_stock' => $b->quantity > 0,
            ])->values(),
        ]);
    }

    // Qualified so the bundle's belongsToMany join doesn't make bare `id` ambiguous.
    private const PICKER_COLS = ['books.id', 'books.title', 'books.author_id', 'books.price', 'books.quantity', 'books.image'];

    private function categoryBooks(int $categoryId, bool $includeChildren): Collection
    {
        $categoryIds = [$categoryId];
        if ($includeChildren) {
            $categoryIds = array_merge($categoryIds, Category::where('parent_id', $categoryId)->pluck('id')->all());
        }

        return Book::query()
            ->where('type', 'book')
            ->standardOnly()
            ->where(function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds)
                  ->orWhereHas('categories', fn($c) => $c->whereIn('categories.id', $categoryIds));
            })
            ->with('primaryAuthor:id,name')
            ->orderBy('title')
            ->limit(300)
            ->get(self::PICKER_COLS);
    }

    /** Preview / search of books matching a price range (active standard books). */
    private function priceRangeBooks(?float $min, ?float $max, ?string $q = null): Collection
    {
        if ($min === null && $max === null) {
            return new Collection();
        }

        $query = Book::where('type', 'book')->standardOnly()->where('status', 'active')
            ->with('primaryAuthor:id,name')
            ->orderBy('price');

        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        if (!empty($q)) {
            $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $query->where('title', 'like', '%' . $term . '%');
        }

        return $query->limit(300)->get(self::PICKER_COLS);
    }

    private function seriesBooks(int $seriesId): Collection
    {
        $series = Series::find($seriesId);
        if (!$series) {
            return new Collection();
        }

        return $series->books()
            ->where('type', 'book')
            ->standardOnly()
            ->with('primaryAuthor:id,name')
            ->limit(300)
            ->get(self::PICKER_COLS);
    }

    private function bundleBooks(int $bundleId): Collection
    {
        $bundle = Book::where('product_type', 'bundle')->find($bundleId);
        if (!$bundle) {
            return new Collection();
        }

        return $bundle->items()
            ->where('type', 'book')
            ->standardOnly()
            ->with('primaryAuthor:id,name')
            ->limit(300)
            ->get(self::PICKER_COLS);
    }

    public function store(StoreOfferRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['excluded_book_ids'] = array_values(array_map('intval', $request->input('excluded_book_ids', [])));
        $data['series_ids'] = array_values(array_map('intval', $request->input('series_ids', [])));
        $data['bundle_ids'] = array_values(array_map('intval', $request->input('bundle_ids', [])));

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $this->adminService->processBookImage($request->file('banner_image'));
        }

        $offer = Offer::create($data);
        $offer->books()->sync($data['book_ids'] ?? []);

        return redirect()->route('admin.offers.index')->with('success', 'تم إنشاء العرض بنجاح.');
    }

    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', false);
        $data['excluded_book_ids'] = array_values(array_map('intval', $request->input('excluded_book_ids', [])));
        $data['series_ids'] = array_values(array_map('intval', $request->input('series_ids', [])));
        $data['bundle_ids'] = array_values(array_map('intval', $request->input('bundle_ids', [])));

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $this->adminService->processBookImage($request->file('banner_image'), $offer->banner_image);
        }

        $offer->update($data);
        $offer->books()->sync($data['book_ids'] ?? []);

        return redirect()->route('admin.offers.index')->with('success', 'تم تحديث العرض بنجاح.');
    }

    public function destroy(Offer $offer)
    {
        if ($offer->banner_image && file_exists(public_path($offer->banner_image))) {
            @unlink(public_path($offer->banner_image));
        }
        $offer->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف العرض.']);
    }

    public function toggleActive(Offer $offer)
    {
        $offer->update(['is_active' => !$offer->is_active]);
        $state = $offer->is_active ? 'مفعّل' : 'معطّل';

        return response()->json([
            'success'   => true,
            'message'   => "العرض الآن {$state}.",
            'is_active' => $offer->is_active,
        ]);
    }
}
