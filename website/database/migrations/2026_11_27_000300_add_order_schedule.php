<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // hours of notice a section needs (banquets: 3); 0 = can be made right away
        Schema::table('categories', function (Blueprint $t) {
            $t->unsignedTinyInteger('lead_hours')->default(0);
        });
        // whole lambs are booked at least 3 hours ahead
        DB::table('categories')->where('slug', 'banquets')->update(['lead_hours' => 3]);
        // when the customer wants the order; null = as soon as possible
        Schema::table('orders', function (Blueprint $t) {
            $t->timestamp('scheduled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $t) => $t->dropColumn('lead_hours'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('scheduled_at'));
    }
};
