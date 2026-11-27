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
        return view('admin.menu', ['categories' => Category::orderBy('sort')->with('items.options')->get()]);
    }

    public function create()
    {
        return view('admin.item', ['item' => new Item(['is_active' => true]), 'categories' => Category::orderBy('sort')->get()]);
    }

    public function edit(Item $item)
    {
        return view('admin.item', ['item' => $item->load('options'), 'categories' => Category::orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        $item = Item::create($this->data($request));
        $this->syncOptions($item, $request);
        return redirect()->route('admin.menu')->with('ok', __('site.admin.saved'));
    }

    public function update(Request $request, Item $item)
    {
        $item->update($this->data($request, $item));
        $this->syncOptions($item, $request);
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
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        unset($data['options']);

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
}
