<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Fish meals with rice show the owner's photo of the fish with its rice plate.
// Only replaces a menu image, never one uploaded from the admin.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['Suprem Fish' => 'fish-suprem-plate', 'Hamour Fish' => 'fish-hamour-plate', 'Salmon Fish' => 'fish-salmon-plate'] as $nameEn => $slug) {
            DB::table('items')->where('name_en', $nameEn)
                ->where(fn ($q) => $q->where('image', 'like', 'images/menu/%')->orWhereNull('image'))
                ->update(['image' => 'images/menu/'.$slug.'.webp', 'updated_at' => now()]);
        }
    }

    public function down(): void {}
};
