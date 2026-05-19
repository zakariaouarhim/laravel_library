<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreSeriesRequest;
use App\Http\Requests\Admin\UpdateSeriesRequest;
use App\Models\Book;
use App\Models\Series;
use App\Models\Author;
use App\Services\ImageService;
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

        return view('series', compact('series', 'books', 'bundles', 'categories'));
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
