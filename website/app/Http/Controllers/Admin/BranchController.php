<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin.branches', ['branches' => Branch::with('areas')->orderBy('sort')->get()]);
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
            'lat' => 'nullable|numeric|between:22,26.5',
            'lng' => 'nullable|numeric|between:51,56.5',
            'areas' => 'nullable|string|max:5000',
        ]);
        $areas = $data['areas'] ?? null;
        unset($data['areas']);
        $data['phones'] = array_values(array_filter(array_map('trim', explode(',', $data['phones'] ?? ''))));
        $data['is_active'] = $request->boolean('is_active');
        $branch->update($data);
        if ($areas !== null || $request->has('areas')) $this->syncAreas($branch, (string) $areas);
        return back()->with('ok', __('site.admin.saved'));
    }

    /**
     * One area per line, "Arabic | English" (English is optional). Areas taken off the list are
     * switched off, not deleted, so old orders keep their area.
     */
    private function syncAreas(Branch $branch, string $text): void
    {
        $keep = [];
        foreach (preg_split('/\R/u', $text) as $i => $line) {
            [$ar, $en] = array_map('trim', explode('|', $line, 2)) + [1 => ''];
            if ($ar === '') continue;
            $area = $branch->areas()->firstOrNew(['name_ar' => $ar]);
            $area->fill(['name_en' => $en !== '' ? $en : $ar, 'sort' => $i, 'is_active' => true])->save();
            $keep[] = $area->id;
        }
        $branch->areas()->whereNotIn('id', $keep)->update(['is_active' => false]);
    }
}
