<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin.branches', ['branches' => Branch::orderBy('sort')->get()]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:120',
            'name_en' => 'required|string|max:120',
            'address_ar' => 'nullable|string|max:255',
            'address_en' => 'nullable|string|max:255',
            'phones' => 'nullable|string|max:255',
            'whatsapp' => ['required', 'regex:/^9715\d{8}$/'],
            'map_url' => 'nullable|url|max:500',
            'hours_ar' => 'nullable|string|max:120',
            'hours_en' => 'nullable|string|max:120',
            'delivery_fee' => 'required|numeric|min:0',
            'min_order' => 'required|numeric|min:0',
        ]);
        $data['phones'] = array_values(array_filter(array_map('trim', explode(',', $data['phones'] ?? ''))));
        $data['is_active'] = $request->boolean('is_active');
        $branch->update($data);
        return back()->with('ok', __('site.admin.saved'));
    }
}
