<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        return view('admin.menu', ['categories' => Category::orderBy('sort')->with('items.options', 'items.addonGroups')->get()]);
    }

    public function create()
    {
        return view('admin.item', ['item' => new Item(['is_active' => true]), 'categories' => Category::orderBy('sort')->get()]);
    }

    public function edit(Item $item)
    {
        return view('admin.item', ['item' => $item->load('options', 'addonGroups.addons'), 'categories' => Category::orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        $item = Item::create($this->data($request));
        $this->syncOptions($item, $request);
        $this->syncAddonGroups($item, $request);
        return redirect()->route('admin.menu')->with('ok', __('site.admin.saved'));
    }

    public function update(Request $request, Item $item)
    {
        $item->update($this->data($request, $item));
        $this->syncOptions($item, $request);
        $this->syncAddonGroups($item, $request);
        return redirect()->route('admin.menu')->with('ok', __('site.admin.saved'));
    }

    public function toggle(Item $item)
    {
        $item->update(['is_active' => ! $item->is_active]);
        return back()->with('ok', __('site.admin.saved'));
    }

    private function data(Request $request, ?Item $item = null): array
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name_ar' => 'required|string|max:120',
            'name_en' => 'required|string|max:120',
            'description_ar' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0|max:100000',
            'image' => 'nullable|image|max:4096',
            'options' => 'array',
            'options.*.name_ar' => 'nullable|string|max:80',
            'options.*.name_en' => 'nullable|string|max:80',
            'options.*.price' => 'nullable|numeric|min:0',
            'groups' => 'array|max:10',
            'groups.*.name_ar' => 'nullable|string|max:80',
            'groups.*.name_en' => 'nullable|string|max:80',
            'groups.*.lines' => 'nullable|string|max:3000',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        unset($data['options'], $data['groups']);

        if ($request->hasFile('image')) {
            $name = uniqid('item-').'.'.$request->file('image')->extension();
            $request->file('image')->move(public_path('uploads'), $name);
            $data['image'] = 'uploads/'.$name;
        } else {
            unset($data['image']);
        }
        return $data;
    }

    private function syncOptions(Item $item, Request $request): void
    {
        $item->options()->delete();
        foreach (array_values($request->input('options', [])) as $i => $o) {
            if (blank($o['name_ar'] ?? null) || ! is_numeric($o['price'] ?? null)) continue;
            $item->options()->create([
                'name_ar' => $o['name_ar'], 'name_en' => $o['name_en'] ?: $o['name_ar'], 'price' => $o['price'], 'sort' => $i,
            ]);
        }
    }

    /**
     * Each group is typed as one choice per line: "Arabic name | English name | price".
     * The price is added to the dish price and may be left out (free).
     */
    private function syncAddonGroups(Item $item, Request $request): void
    {
        $item->addonGroups()->delete();
        $sort = 0;
        foreach (array_values($request->input('groups', [])) as $g) {
            if (blank($g['name_ar'] ?? null)) continue;
            $lines = collect(preg_split('/\R/', (string) ($g['lines'] ?? '')))
                ->map(fn ($l) => array_map('trim', explode('|', $l)))
                ->filter(fn ($p) => filled($p[0]));
            if ($lines->isEmpty()) continue;
            $group = $item->addonGroups()->create([
                'name_ar' => $g['name_ar'], 'name_en' => ($g['name_en'] ?? null) ?: $g['name_ar'],
                'required' => ! empty($g['required']), 'multiple' => ! empty($g['multiple']), 'sort' => $sort++,
            ]);
            foreach ($lines->values() as $i => $p) {
                $group->addons()->create([
                    'name_ar' => $p[0], 'name_en' => ($p[1] ?? '') ?: $p[0],
                    'price' => is_numeric($p[2] ?? null) ? max(0, (float) $p[2]) : 0, 'sort' => $i,
                ]);
            }
        }
    }
}
