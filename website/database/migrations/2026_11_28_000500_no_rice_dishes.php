<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Lamb, chicken and fish meals are also sold without rice, as their own menu items at 8 AED less
// (owner's choice, 2026-11-28). Each twin goes right after its dish and gets the extras but no rice choice.
// Skips a twin that already exists, so it never overwrites what the owner edited.
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach (DatabaseSeeder::NO_RICE_SECTIONS as $slug) {
            $cat = DB::table('categories')->where('slug', $slug)->value('id');
            if (! $cat) continue;
            $items = DB::table('items')->where('category_id', $cat)->orderBy('sort')->orderBy('id')->get();
            $order = [];
            foreach ($items as $it) {
                if (str_ends_with($it->name_en, ' (no rice)')) continue;
                $order[] = $it->id;
                $hasOptions = DB::table('item_options')->where('item_id', $it->id)->exists();
                if ($it->price === null || $hasOptions || in_array($it->name_en, DatabaseSeeder::NO_RICE_SKIP, true)) continue;
                $twinName = $it->name_en.' (no rice)';
                $twin = DB::table('items')->where('name_en', $twinName)->value('id');
                if (! $twin) {
                    $twin = DB::table('items')->insertGetId([
                        'category_id' => $cat, 'name_ar' => $it->name_ar.' بدون رز', 'name_en' => $twinName,
                        'description_ar' => $it->description_ar, 'description_en' => $it->description_en,
                        'price' => max(0, (float) $it->price - DatabaseSeeder::NO_RICE_DISCOUNT), 'image' => $it->image,
                        'is_active' => $it->is_active, 'is_featured' => false, 'branch_ids' => $it->branch_ids,
                        'sort' => 0, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                    // the same extras as the dish (chutney, yogurt, salad), without the rice choice
                    $extras = DB::table('item_addon_groups')->where('item_id', $it->id)->where('name_en', '!=', 'Rice type')->orderBy('sort')->get();
                    foreach ($extras as $gi => $g) {
                        $gid = DB::table('item_addon_groups')->insertGetId([
                            'item_id' => $twin, 'name_ar' => $g->name_ar, 'name_en' => $g->name_en, 'multiple' => $g->multiple,
                            'required' => $g->required, 'sort' => $gi, 'created_at' => $now, 'updated_at' => $now,
                        ]);
                        foreach (DB::table('item_addons')->where('item_addon_group_id', $g->id)->orderBy('sort')->get() as $a) {
                            DB::table('item_addons')->insert([
                                'item_addon_group_id' => $gid, 'name_ar' => $a->name_ar, 'name_en' => $a->name_en,
                                'price' => $a->price, 'sort' => $a->sort, 'created_at' => $now, 'updated_at' => $now,
                            ]);
                        }
                    }
                }
                $order[] = $twin;
            }
            foreach ($order as $i => $id) DB::table('items')->where('id', $id)->update(['sort' => $i]);
        }
    }

    public function down(): void
    {
        DB::table('items')->where('name_en', 'like', '% (no rice)')->delete();
    }
};
