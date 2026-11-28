<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Meals with rice go back to their printed-menu (PSD) picture, which shows the rice;
// the owner's plates without rice now belong only to the "without rice" dishes.
// Only touches images that are still a menu image, never one uploaded from the admin.
return new class extends Migration
{
    public function up(): void
    {
        $psd = [];
        foreach (json_decode(file_get_contents(database_path('data/menu.json')), true)['categories'] as $c) {
            foreach ($c['items'] as $i) $psd[$i['name_en']] = $i['image'] ?? null;
        }
        $menuImage = fn ($q) => $q->where('image', 'like', 'images/menu/%')->orWhereNull('image');

        foreach (DatabaseSeeder::RICE_PSD_RESTORED as $nameEn) {
            if (empty($psd[$nameEn]) || ! file_exists(public_path('images/menu/'.$psd[$nameEn].'.webp'))) continue;
            DB::table('items')->where('name_en', $nameEn)->where($menuImage)
                ->update(['image' => 'images/menu/'.$psd[$nameEn].'.webp', 'updated_at' => now()]);
        }
        foreach (DatabaseSeeder::NO_RICE_PHOTOS as $nameEn => $slug) {
            DB::table('items')->where('name_en', $nameEn.' (no rice)')->where($menuImage)
                ->update(['image' => 'images/menu/'.$slug.'.webp', 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
