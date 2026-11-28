<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Use the real photos the owner sent for these dishes and drinks.
// Only replaces the original printed-menu image, never one uploaded from the admin.
return new class extends Migration
{
    public function up(): void
    {
        foreach (DatabaseSeeder::PHOTOS as $nameEn => $slug) {
            DB::table('items')->where('name_en', $nameEn)
                ->where(fn ($q) => $q->where('image', 'like', 'images/menu/%')->orWhereNull('image'))
                ->update(['image' => 'images/menu/'.$slug.'.webp', 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
