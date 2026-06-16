<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreHomeCarouselRequest;
use App\Http\Requests\Admin\UpdateHomeCarouselRequest;
use App\Models\Author;
use App\Models\Category;
use App\Models\HomeCarousel;
use Illuminate\Support\Facades\Cache;

class AdminHomeCarouselController extends Controller
{
    public function index()
    {
        $carousels = HomeCarousel::with(['author:id,name', 'categories:id,name'])
            ->withCount('books')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        // Parent categories with their children, for the collapsible checkbox tree.
        $categoryTree = Category::whereNull('parent_id')
            ->with(['children' => fn($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $authors = Author::orderBy('name')->get(['id', 'name']);

        return view('Dashbord_Admin.home-carousels', compact('carousels', 'categoryTree', 'authors'));
    }

    public function store(StoreHomeCarouselRequest $request)
    {
        $carousel = HomeCarousel::create($this->baseData($request));
        $this->syncSource($carousel, $request);
        $this->forgetCache();

        return redirect()->route('admin.home-carousels.index')->with('success', 'تم إنشاء الكاروسيل بنجاح.');
    }

    public function update(UpdateHomeCarouselRequest $request, HomeCarousel $home_carousel)
    {
        if ($home_carousel->is_system) {
            // Built-in carousels: only presentation knobs are editable; source is code-driven.
            $home_carousel->update([
                'title'            => $request->input('title'),
                'book_limit'       => $request->input('book_limit', $home_carousel->book_limit),
                'sort_order'       => $request->input('sort_order', $home_carousel->sort_order),
                'is_active'        => $request->boolean('is_active'),
                'show_unavailable' => $request->boolean('show_unavailable'),
            ]);
        } else {
            $home_carousel->update($this->baseData($request));
            $this->syncSource($home_carousel, $request);
        }

        $this->forgetCache();

        return redirect()->route('admin.home-carousels.index')->with('success', 'تم تحديث الكاروسيل بنجاح.');
    }

    public function destroy(HomeCarousel $home_carousel)
    {
        // Built-in carousels can't be deleted — only hidden via the toggle.
        if ($home_carousel->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'الكاروسيلات المدمجة لا يمكن حذفها، يمكنك إخفاؤها فقط.',
            ], 422);
        }

        $home_carousel->delete();
        $this->forgetCache();

        return response()->json(['success' => true, 'message' => 'تم حذف الكاروسيل.']);
    }

    public function toggleActive(HomeCarousel $home_carousel)
    {
        $home_carousel->update(['is_active' => !$home_carousel->is_active]);
        $this->forgetCache();
        $state = $home_carousel->is_active ? 'مفعّل' : 'معطّل';

        return response()->json([
            'success'   => true,
            'message'   => "الكاروسيل الآن {$state}.",
            'is_active' => $home_carousel->is_active,
        ]);
    }

    private function baseData($request): array
    {
        $type = $request->input('source_type');

        return [
            'title'       => $request->input('title'),
            'source_type' => $type,
            // Language filter only applies to author/categories sources.
            'language'    => in_array($type, ['author', 'categories'], true) ? ($request->input('language') ?: null) : null,
            'author_id'   => $type === 'author' ? $request->input('author_id') : null,
            'book_limit'  => $request->input('book_limit', 12),
            'sort_order'  => $request->input('sort_order', 0),
            'is_active'   => $request->boolean('is_active'),
            'show_unavailable' => $request->boolean('show_unavailable'),
        ];
    }

    /**
     * Keep only the pivot rows relevant to the chosen source; clear the others.
     */
    private function syncSource(HomeCarousel $carousel, $request): void
    {
        $type = $request->input('source_type');

        $carousel->categories()->sync($type === 'categories' ? ($request->input('category_ids', [])) : []);
        $carousel->books()->sync($type === 'manual' ? ($request->input('book_ids', [])) : []);

        // Bump updated_at so the per-carousel homepage cache key (keyed on it) refreshes.
        $carousel->touch();
    }

    private function forgetCache(): void
    {
        Cache::forget('home_carousels_active'); // legacy key
        Cache::forget(\App\Services\HomeCarouselService::CACHE_KEY);
    }
}
