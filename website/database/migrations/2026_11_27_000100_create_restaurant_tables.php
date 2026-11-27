<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->json('phones')->nullable();
            $table->string('whatsapp')->nullable(); // international format, digits only e.g. 971581404800
            $table->string('map_url')->nullable();
            $table->string('hours_ar')->nullable();
            $table->string('hours_en')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('min_order', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('note_ar')->nullable();
            $table->string('note_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->decimal('price', 8, 2)->nullable(); // null when the item is priced by option
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('item_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('branch_id')->constrained();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->enum('fulfillment', ['delivery', 'pickup'])->default('delivery');
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->enum('payment_method', ['card', 'whatsapp', 'cash'])->default('whatsapp');
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed', 'refunded'])->default('unpaid');
            $table->enum('status', ['new', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'])->default('new');
            $table->string('stripe_session_id')->nullable()->index();
            $table->string('source')->default('web'); // web | app
            $table->string('locale', 2)->default('ar');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->decimal('unit_price', 8, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('item_options');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('branches');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
