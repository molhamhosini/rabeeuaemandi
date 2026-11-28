<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// "Without rice" dishes must not show rice in their photo.
// Only replaces a printed-menu image, never one uploaded from the admin.
return new class extends Migration
{
    public function up(): void
    {
        foreach (DatabaseSeeder::NO_RICE_PHOTOS as $nameEn => $slug) {
            DB::table('items')->where('name_en', $nameEn.' (no rice)')
                ->where(fn ($q) => $q->where('image', 'like', 'images/menu/%')->orWhereNull('image'))
                ->update(['image' => 'images/menu/'.$slug.'.webp', 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
