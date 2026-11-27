<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extra choices on a dish, e.g. rice type, serving, extras (chutney, yogurt).
        Schema::create('item_addon_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->boolean('multiple')->default(false); // tick several, or pick one
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_addon_group_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->decimal('price', 8, 2)->default(0); // added to the dish price
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('addons')->nullable()->after('item_option_id');
            $table->text('details_ar')->nullable()->after('name_en');
            $table->text('details_en')->nullable()->after('details_ar');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn(['addons', 'details_ar', 'details_en']));
        Schema::dropIfExists('item_addons');
        Schema::dropIfExists('item_addon_groups');
    }
};
