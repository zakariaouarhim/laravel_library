<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class AdminFaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::orderBy('display_order')->orderBy('id')->get();
        return view('Dashbord_Admin.faqs', compact('faqs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question'      => 'required|string|max:255',
            'answer'        => 'required|string|max:5000',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
        ]);
        $data['is_active']     = $request->boolean('is_active', true);
        $data['display_order'] = $data['display_order'] ?? (int) (Faq::max('display_order') + 10);

        Faq::create($data);

        return redirect()->route('admin.faqs.index')->with('success', 'تمت إضافة السؤال بنجاح');
    }

    public function update(Request $request, Faq $faq)
    {
        $data = $request->validate([
            'question'      => 'required|string|max:255',
            'answer'        => 'required|string|max:5000',
            'display_order' => 'nullable|integer|min:0',
            'is_active'     => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', false);

        $faq->update($data);

        return redirect()->route('admin.faqs.index')->with('success', 'تم التحديث بنجاح');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        return redirect()->route('admin.faqs.index')->with('success', 'تم الحذف بنجاح');
    }
}
