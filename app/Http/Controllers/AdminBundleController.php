<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreBundleRequest;
use App\Http\Requests\Admin\UpdateBundleRequest;
use App\Models\Book;
use App\Models\Series;
use App\Services\BookAdminService;
use Illuminate\Support\Facades\DB;

class AdminBundleController extends Controller
{
    public function __construct(private readonly BookAdminService $adminService) {}

    public function index()
    {
        $bundles = Book::onlyBundles()
            ->with(['series', 'items'])
            ->orderByDesc('id')
            ->get();

        $stats = [
            'total'      => $bundles->count(),
            'in_stock'   => $bundles->where('quantity', '>', 0)->count(),
            'out_stock'  => $bundles->where('quantity', '<=', 0)->count(),
        ];

        $series = Series::orderBy('name')->get(['id', 'name']);

        return view('Dashbord_Admin.bundles', compact('bundles', 'stats', 'series'));
    }

    public function seriesBooks(Series $series)
    {
        $books = Book::standardOnly()
            ->where('series_id', $series->id)
            ->orderBy('volume_number')
            ->get(['id', 'title', 'volume_number', 'price', 'quantity']);

        return response()->json($books);
    }

    public function store(StoreBundleRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->adminService->processBookImage($request->file('image'));
            }

            $bundle = Book::create([
                'title'        => $validated['title'],
                'description'  => $validated['description'] ?? null,
                'price'        => $validated['price'],
                'quantity'     => $validated['quantity'],
                'product_type' => 'bundle',
                'type'         => 'book',
                'status'       => 'active',
                'series_id'    => $validated['series_id'],
                'image'        => $imagePath,
            ]);

            $sync = [];
            foreach ($validated['items'] as $item) {
                $sync[$item['book_id']] = ['quantity' => $item['quantity']];
            }
            $bundle->items()->sync($sync);

            $this->inheritFromItems($bundle);
        });

        return back()->with('success', 'تم إنشاء الباقة بنجاح.');
    }

    public function update(UpdateBundleRequest $request, Book $bundle)
    {
        abort_unless($bundle->isBundle(), 404);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $bundle) {
            $data = [
                'title'       => $validated['title'],
                'description' => $validated['description'] ?? null,
                'price'       => $validated['price'],
                'quantity'    => $validated['quantity'],
                'series_id'   => $validated['series_id'],
            ];

            if ($request->hasFile('image')) {
                $data['image'] = $this->adminService->processBookImage($request->file('image'), $bundle->image);
            }

            $bundle->update($data);

            $sync = [];
            foreach ($validated['items'] as $item) {
                $sync[$item['book_id']] = ['quantity' => $item['quantity']];
            }
            $bundle->items()->sync($sync);

            $this->inheritFromItems($bundle);
        });

        return response()->json(['success' => true, 'message' => 'تم تحديث الباقة بنجاح.']);
    }

    public function destroy(Book $bundle)
    {
        abort_unless($bundle->isBundle(), 404);

        if ($bundle->image && file_exists(public_path($bundle->image))) {
            @unlink(public_path($bundle->image));
        }

        $bundle->items()->detach();
        $bundle->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الباقة بنجاح.']);
    }

    /**
     * Inherit authors + categories from the bundle's member volumes,
     * so admins don't re-enter metadata the individual books already have.
     *
     * - bundle.author_id / category_id ← primary values from the first item (by volume_number)
     * - bundle.authors() pivot ← union of all items' author pivots (primary author stays 'primary')
     * - bundle.categories() pivot ← union of all items' category pivots (is_primary flag preserved for the chosen primary)
     */
    private function inheritFromItems(Book $bundle): void
    {
        $items = $bundle->items()
            ->with(['authors', 'categories'])
            ->orderBy('volume_number')
            ->get();

        if ($items->isEmpty()) return;

        $firstItem = $items->first();
        $primaryAuthorId   = $firstItem->author_id;
        $primaryCategoryId = $firstItem->category_id;

        $languages = $items->pluck('language')->filter();
        $language  = $languages->isNotEmpty()
            ? $languages->countBy()->sortDesc()->keys()->first()
            : $firstItem->language;

        $bundle->update([
            'author_id'   => $primaryAuthorId,
            'category_id' => $primaryCategoryId,
            'language'    => $language,
        ]);

        // Union of authors across items — keep the chosen primary as 'primary',
        // everyone else collapsed to 'co-author' (a contributor is still a contributor).
        $authorSync = [];
        foreach ($items as $item) {
            foreach ($item->authors as $author) {
                if (isset($authorSync[$author->id])) continue;
                $type = ($author->id == $primaryAuthorId)
                    ? 'primary'
                    : ($author->pivot->author_type ?? 'co-author');
                if ($type === 'primary' && $author->id != $primaryAuthorId) {
                    $type = 'co-author';
                }
                $authorSync[$author->id] = ['author_type' => $type];
            }
        }
        if ($authorSync) {
            $bundle->authors()->sync($authorSync);
        }

        // Union of categories — flag is_primary only on the bundle's chosen primary category.
        $categorySync = [];
        foreach ($items as $item) {
            foreach ($item->categories as $category) {
                if (isset($categorySync[$category->id])) continue;
                $categorySync[$category->id] = [
                    'is_primary' => ($category->id == $primaryCategoryId),
                ];
            }
        }
        if ($categorySync) {
            $bundle->categories()->sync($categorySync);
        }
    }

    public function show(Book $bundle)
    {
        abort_unless($bundle->isBundle(), 404);

        $bundle->load(['series', 'items' => function ($q) {
            $q->orderBy('volume_number');
        }]);

        return response()->json([
            'id'          => $bundle->id,
            'title'       => $bundle->title,
            'description' => $bundle->description,
            'price'       => $bundle->price,
            'quantity'    => $bundle->quantity,
            'image'       => $bundle->image,
            'series_id'   => $bundle->series_id,
            'items'       => $bundle->items->map(fn($b) => [
                'book_id'       => $b->id,
                'title'         => $b->title,
                'volume_number' => $b->volume_number,
                'price'         => $b->price,
                'quantity'      => $b->pivot->quantity,
            ]),
        ]);
    }
}
