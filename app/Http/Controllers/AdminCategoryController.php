<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('books')
            ->with(['children' => fn ($q) => $q->withCount('books')])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $stats = [
            'total'    => Category::count(),
            'parents'  => Category::whereNull('parent_id')->count(),
            'children' => Category::whereNotNull('parent_id')->count(),
        ];

        // Flat list of parents for the "parent" dropdown in the create/edit modals
        $parentOptions = $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);

        return view('Dashbord_Admin.categories', compact('categories', 'stats', 'parentOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'parent_id'       => 'nullable|exists:categories,id',
            'categorie_icon'  => 'nullable|string|max:100',
            'categorie_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required'   => 'اسم الفئة مطلوب',
            'name.max'        => 'اسم الفئة طويل جداً (الحد 100 حرف)',
            'parent_id.exists'=> 'الفئة الأم المختارة غير موجودة',
        ]);

        // Guard: prevent grandchild (chosen parent must itself be a root)
        if (!empty($validated['parent_id'])) {
            $parent = Category::find($validated['parent_id']);
            if ($parent->parent_id !== null) {
                return back()->withErrors(['parent_id' => 'لا يمكن إضافة فئة فرعية داخل فئة فرعية أخرى'])->withInput();
            }
        }

        if ($request->hasFile('categorie_image')) {
            $validated['categorie_image'] = $request->file('categorie_image')
                ->store('categories', 'public');
        }

        Category::create($validated);

        return back()->with('success', 'تم إنشاء الفئة بنجاح.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'parent_id'       => 'nullable|exists:categories,id',
            'categorie_icon'  => 'nullable|string|max:100',
            'categorie_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required'    => 'اسم الفئة مطلوب',
            'parent_id.exists' => 'الفئة الأم المختارة غير موجودة',
        ]);

        // Guard: a parent that has children cannot become a child itself
        if (!empty($validated['parent_id']) && $category->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحويل فئة رئيسية تحتوي على فئات فرعية إلى فئة فرعية',
            ], 422);
        }

        // Guard: circular reference
        if ((int) ($validated['parent_id'] ?? 0) === $category->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن أن تكون الفئة أباً لنفسها',
            ], 422);
        }

        // Guard: prevent grandchild
        if (!empty($validated['parent_id'])) {
            $parent = Category::find($validated['parent_id']);
            if ($parent && $parent->parent_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إضافة فئة فرعية داخل فئة فرعية أخرى',
                ], 422);
            }
        }

        if ($request->hasFile('categorie_image')) {
            if ($category->categorie_image) {
                Storage::disk('public')->delete($category->categorie_image);
            }
            $validated['categorie_image'] = $request->file('categorie_image')
                ->store('categories', 'public');
        } else {
            // Keep existing image; don't overwrite with null
            unset($validated['categorie_image']);
        }

        $category->update($validated);

        return response()->json(['success' => true, 'message' => 'تم تحديث الفئة بنجاح.']);
    }

    public function destroy(Category $category)
    {
        if ($category->books()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذه الفئة لأنها تحتوي على كتب. يرجى نقل الكتب أولاً.',
            ], 422);
        }

        if ($category->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف فئة رئيسية تحتوي على فئات فرعية. يرجى حذف الفئات الفرعية أولاً.',
            ], 422);
        }

        if ($category->categorie_image) {
            Storage::disk('public')->delete($category->categorie_image);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الفئة بنجاح.']);
    }
}
