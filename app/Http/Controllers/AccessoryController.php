<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;

class AccessoryController extends Controller
{
    /**
     * Public accessories page
     */
    public function index(Request $request)
    {
        $request->validate([
            'category'  => 'nullable|integer',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'sort'      => 'nullable|in:newest,price_asc,price_desc,title',
        ]);

        $query = Book::accessories();

        // Category filter
        if ($request->filled('category')) {
            $category = Category::find($request->category);
            if ($category) {
                $childIds = $category->children->pluck('id')->toArray();
                $allIds = array_merge([$category->id], $childIds);
                $query->whereIn('category_id', $allIds);
            }
        }

        // Price range filters
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        // Sorting
        switch ($request->input('sort')) {
            case 'newest':
                $query->latest();
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->latest();
                break;
        }

        $accessories = $query->paginate(12)->appends($request->query());

        // Get categories that have accessories
        $categories = Category::whereHas('books', function ($q) {
            $q->where('type', 'accessory');
        })->get();

        return view('accessories', compact('accessories', 'categories'));
    }

    /**
     * Admin accessories list
     */
    public function adminIndex(Request $request)
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'category' => 'nullable|integer',
        ]);

        $query = Book::accessories()->with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $category = Category::with('children')->find($request->category);
            if ($category) {
                $categoryIds = collect([
                    $category->id,
                    ...$category->children->pluck('id')
                ])->filter()->unique();
                $query->whereIn('category_id', $categoryIds);
            }
        }

        $accessories = $query->latest()->paginate(15)->withQueryString();

        $categories = Category::whereNull('parent_id')->with('children')->get();

        $totalAccessories = Book::accessories()->count();
        $availableAccessories = Book::accessories()->where('Quantity', '>', 0)->count();
        $totalCategories = Book::accessories()->distinct('category_id')->count('category_id');

        return view('Dashbord_Admin.accessories', compact(
            'accessories',
            'totalAccessories',
            'availableAccessories',
            'totalCategories',
            'categories'
        ));
    }

    /**
     * Store new accessory
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'Quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.webp';
                $destinationPath = public_path('images/accessories');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $image = Image::read($file);
                $image->scale(width: 400);
                $image->toWebp(80)->save($destinationPath . '/' . $imageName);

                $imagePath = 'images/accessories/' . $imageName;
            } catch (\Exception $e) {
                \Log::error('Accessory image upload failed: ' . $e->getMessage());
                return back()->withErrors(['image' => 'فشل رفع الصورة'])->withInput();
            }
        }

        Book::create([
            'title' => $validated['title'],
            'type' => 'accessory',
            'description' => $validated['description'],
            'price' => $validated['price'],
            'discount' => $validated['discount'] ?? null,
            'category_id' => $validated['category_id'],
            'Quantity' => $validated['Quantity'],
            'image' => $imagePath,
        ]);

        return back()->with('success', 'تمت إضافة الإكسسوار بنجاح');
    }

    /**
     * Show accessory details (JSON for AJAX)
     */
    public function adminShow($id)
    {
        $accessory = Book::accessories()->with('category')->findOrFail($id);
        return response()->json($accessory);
    }

    /**
     * Update accessory
     */
    public function adminUpdate(Request $request, $id)
    {
        $accessory = Book::accessories()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'Quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $imageName = time() . '_' . uniqid() . '.webp';
                $destinationPath = public_path('images/accessories');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $image = Image::read($file);
                $image->scale(width: 400);
                $image->toWebp(80)->save($destinationPath . '/' . $imageName);

                $validated['image'] = 'images/accessories/' . $imageName;
            } catch (\Exception $e) {
                \Log::error('Accessory image update failed: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'فشل رفع الصورة'], 500);
            }
        } else {
            unset($validated['image']);
        }

        $accessory->update($validated);

        return response()->json(['success' => true, 'message' => 'تم تحديث الإكسسوار بنجاح']);
    }

    /**
     * Delete accessory
     */
    public function adminDestroy($id)
    {
        $accessory = Book::accessories()->findOrFail($id);
        $accessory->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الإكسسوار بنجاح']);
    }
}
