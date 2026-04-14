<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'author_id'     => 'nullable|exists:authors,id',
            'total_volumes' => 'nullable|integer|min:1|max:9999',
            'is_complete'   => 'nullable|boolean',
            'cover_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required'      => 'اسم السلسلة مطلوب',
            'name.max'           => 'اسم السلسلة طويل جداً (الحد 255 حرف)',
            'author_id.exists'   => 'المؤلف المختار غير موجود',
            'total_volumes.min'  => 'عدد الأجزاء يجب أن يكون 1 على الأقل',
            'cover_image.image'  => 'الملف يجب أن يكون صورة',
            'cover_image.max'    => 'حجم الصورة يجب ألا يتجاوز 2MB',
        ]);

        $validated['is_complete'] = $request->boolean('is_complete');

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = app(ImageService::class)
                ->processSeriesImage($request->file('cover_image'));
        }

        Series::create($validated);

        return back()->with('success', 'تم إنشاء السلسلة بنجاح.');
    }

    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'author_id'     => 'nullable|exists:authors,id',
            'total_volumes' => 'nullable|integer|min:1|max:9999',
            'is_complete'   => 'nullable|boolean',
            'cover_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required'    => 'اسم السلسلة مطلوب',
            'author_id.exists' => 'المؤلف المختار غير موجود',
        ]);

        $validated['is_complete'] = $request->boolean('is_complete');

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

    public function publicShow($id)
    {
        $series = Series::with('author')->withCount('books')->findOrFail($id);

        $books = Book::with('primaryAuthor')
            ->where('series_id', $series->id)
            ->withCount('reviews')
            ->withAvg('reviews as reviews_avg_rating', 'rating')
            ->orderBy('volume_number')
            ->paginate(24);

        return view('series', compact('series', 'books'));
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
