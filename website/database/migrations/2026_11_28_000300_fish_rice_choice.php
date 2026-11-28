<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Fish dishes come with rice too: give each one the same "rice type" choice as lamb and chicken.
// Only adds the group where a fish dish has none, so it never touches what the owner edited.
return new class extends Migration
{
    private const RICE = [
        ['مندي', 'Mandi'], ['بخاري', 'Bukhari'], ['زربيان', 'Zurbian'], ['كبسة', 'Kabsa'], ['بشاور', 'Beshawer'],
    ];

    public function up(): void
    {
        $fish = DB::table('categories')->where('slug', 'fish')->value('id');
        if (! $fish) return;
        $now = now();
        foreach (DB::table('items')->where('category_id', $fish)->pluck('id') as $itemId) {
            $has = DB::table('item_addon_groups')->where('item_id', $itemId)->where('name_en', 'Rice type')->exists();
            if ($has) continue;
            DB::table('item_addon_groups')->where('item_id', $itemId)->increment('sort');
            $group = DB::table('item_addon_groups')->insertGetId([
                'item_id' => $itemId, 'name_ar' => 'نوع الرز', 'name_en' => 'Rice type',
                'multiple' => true, 'required' => false, 'sort' => 0, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (self::RICE as $i => [$ar, $en]) {
                DB::table('item_addons')->insert([
                    'item_addon_group_id' => $group, 'name_ar' => $ar, 'name_en' => $en, 'price' => 0, 'sort' => $i,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // the rice choice stays; the owner can remove it per dish in the admin
    }
};
