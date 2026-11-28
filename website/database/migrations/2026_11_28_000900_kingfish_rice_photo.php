<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Kingfish with rice shows the fish next to a rice plate, like the other fish meals.
// Only replaces a menu image, never one uploaded from the admin.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('items')->where('name_en', 'Kingfish')
            ->where(fn ($q) => $q->where('image', 'like', 'images/menu/%')->orWhereNull('image'))
            ->update(['image' => 'images/menu/fish-kingfish-plate.webp', 'updated_at' => now()]);
    }

    public function down(): void {}
};
