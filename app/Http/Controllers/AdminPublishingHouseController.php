<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StorePublishingHouseRequest;
use App\Http\Requests\Admin\UpdatePublishingHouseRequest;
use App\Models\PublishingHouse;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminPublishingHouseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = PublishingHouse::withCount('books');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['active', 'inactive'])) {
            $query->where('status', $status);
        }

        $publishers = $query->orderBy('name')->paginate(15)->appends($request->query());

        $stats = PublishingHouse::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
            COUNT(DISTINCT NULLIF(country, '')) as countries_count
        ")->first();

        return view('Dashbord_Admin.publishing_houses', [
            'publishers'         => $publishers,
            'totalPublishers'    => $stats->total,
            'activePublishers'   => (int) $stats->active_count,
            'inactivePublishers' => (int) $stats->inactive_count,
            'totalCountries'     => (int) $stats->countries_count,
            'search'             => $search,
            'statusFilter'       => $status,
        ]);
    }

    public function store(StorePublishingHouseRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            $validated['logo'] = app(ImageService::class)
                ->processPublisherLogo($request->file('logo'));
        } else {
            unset($validated['logo']);
        }

        PublishingHouse::create($validated);

        return redirect()->route('admin.publishing_houses.index')
            ->with('success', 'تم إضافة دار النشر بنجاح.');
    }

    public function show($id)
    {
        $publisher = PublishingHouse::withCount('books')->findOrFail($id);

        return response()->json($publisher);
    }

    public function update(UpdatePublishingHouseRequest $request, $id)
    {
        $publisher = PublishingHouse::findOrFail($id);
        $validated = $request->validated();

        if ($request->hasFile('logo')) {
            $validated['logo'] = app(ImageService::class)
                ->processPublisherLogo($request->file('logo'), $publisher->logo);
        } else {
            unset($validated['logo']);
        }

        $publisher->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث دار النشر بنجاح.',
            ]);
        }

        return redirect()->route('admin.publishing_houses.index')
            ->with('success', 'تم تحديث دار النشر بنجاح.');
    }

    public function destroy($id)
    {
        $publisher = PublishingHouse::withCount('books')->findOrFail($id);

        if ($publisher->books_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف دار نشر مرتبطة بكتب. يرجى نقل الكتب أولاً.',
            ], 422);
        }

        if ($publisher->logo && Storage::disk('public')->exists($publisher->logo)) {
            Storage::disk('public')->delete($publisher->logo);
        }

        $publisher->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف دار النشر بنجاح.',
        ]);
    }
}
