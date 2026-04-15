<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Series;
use App\Services\BookAdminService;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'series_id'   => 'required|exists:series,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'items'       => 'required|array|min:1',
            'items.*.book_id'  => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
        ], [
            'series_id.required' => 'يجب اختيار السلسلة',
            'title.required'     => 'اسم الباقة مطلوب',
            'price.required'     => 'السعر مطلوب',
            'quantity.required'  => 'الكمية مطلوبة',
            'items.required'     => 'يجب اختيار جزء واحد على الأقل',
            'items.min'          => 'يجب اختيار جزء واحد على الأقل',
        ]);

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
        });

        return back()->with('success', 'تم إنشاء الباقة بنجاح.');
    }

    public function update(Request $request, Book $bundle)
    {
        abort_unless($bundle->isBundle(), 404);

        $validated = $request->validate([
            'series_id'   => 'required|exists:series,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:0',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'items'       => 'required|array|min:1',
            'items.*.book_id'  => 'required|exists:books,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
        ]);

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
