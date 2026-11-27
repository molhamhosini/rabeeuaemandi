<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // the cooking service cooks a whole lamb too, so it is booked ahead like banquets
    public function up(): void
    {
        DB::table('categories')->where('slug', 'cooking-service')->update(['lead_hours' => 3]);
    }

    public function down(): void
    {
        DB::table('categories')->where('slug', 'cooking-service')->update(['lead_hours' => 0]);
    }
};
