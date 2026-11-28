<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The pin the customer drops on the map in the app, for the driver. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['orders', 'customers'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->decimal('lat', 9, 6)->nullable()->after('address');
                $table->decimal('lng', 9, 6)->nullable()->after('lat');
            });
        }
    }

    public function down(): void
    {
        foreach (['orders', 'customers'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropColumn(['lat', 'lng']));
        }
    }
};
