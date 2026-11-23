<?php

namespace App\Http\Controllers;

use App\Models\SchoolOrder;
use Illuminate\Http\Request;

/**
 * Counter screen for back-to-school pickup orders.
 * Staff creates an order (the id is the number written on the bag), then finds
 * it later by typing the customer's phone or name on a tablet at the counter.
 */
class CounterController extends Controller
{
    /** Screen 1 (main): the big search screen. */
    public function index()
    {
        return view('counter.search');
    }

    /**
     * Live-search JSON endpoint.
     * Partial match on phone (digits-normalized) OR customer name OR the order
     * number itself. Excludes collected orders unless ?all=1.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $showAll = $request->boolean('all');

        if ($q === '') {
            return response()->json([]);
        }

        $digits = SchoolOrder::normalizePhone($q);

        $orders = SchoolOrder::query()
            ->when(! $showAll, fn ($query) => $query->where('status', 'pending'))
            ->where(function ($query) use ($q, $digits) {
                $query->where('customer_name', 'LIKE', "%{$q}%");
                if ($digits !== '') {
                    $query->orWhere('phone', 'LIKE', "%{$digits}%");
                }
                // Typing "64" also finds order number 64 directly.
                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'customer_name', 'phone', 'shelf', 'status']);

        return response()->json($orders);
    }

    /** Screen 2: add-order form. */
    public function addForm()
    {
        return view('counter.add');
    }

    /** Store a new order; the auto-increment id becomes the order number. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:150',
            'phone'         => 'required|string|max:30',
            'shelf'         => 'nullable|string|max:50',
        ], [], [
            'customer_name' => 'اسم الزبون',
            'phone'         => 'رقم الهاتف',
            'shelf'         => 'الرف',
        ]);

        $data['phone'] = SchoolOrder::normalizePhone($data['phone']);

        $order = SchoolOrder::create($data);

        // Back to the form with the new number flashed BIG so staff can write
        // it on the bag, ready to type the next order immediately.
        return redirect()
            ->route('counter.add')
            ->with('new_order', $order->only(['id', 'customer_name', 'shelf']));
    }

    /** Mark an order as collected (AJAX from a search-result card). */
    public function collect(SchoolOrder $order)
    {
        $order->update(['status' => 'collected', 'collected_at' => now()]);

        return response()->json(['ok' => true, 'id' => $order->id]);
    }

    /** Undo a mistaken "collected" tap (AJAX, from the undo toast). */
    public function uncollect(SchoolOrder $order)
    {
        $order->update(['status' => 'pending', 'collected_at' => null]);

        return response()->json(['ok' => true, 'id' => $order->id]);
    }
}
