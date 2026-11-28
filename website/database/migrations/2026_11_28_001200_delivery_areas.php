<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery areas: the customer picks their area (or drops a map pin) and the area decides the
 * branch. Al Danah serves Abu Dhabi city; New Shahamah serves the areas around Shahamah.
 * The owner edits the lists on the admin branches page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('lat', 9, 6)->nullable()->after('map_url');
            $table->decimal('lng', 9, 6)->nullable()->after('lat');
        });
        foreach (['orders', 'customers'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('area_id')->nullable()->after('address')->constrained()->nullOnDelete();
            });
        }

        \App\Support\DeliveryAreas::seed();
    }

    public function down(): void
    {
        foreach (['orders', 'customers'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropConstrainedForeignId('area_id'));
        }
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn(['lat', 'lng']));
        Schema::dropIfExists('areas');
    }
};
