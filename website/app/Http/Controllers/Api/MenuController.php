<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Services\OrderService;

class MenuController extends Controller
{
    public function config()
    {
        return [
            'currency' => 'AED',
            'card_payment' => OrderService::cardEnabled(),
            'whatsapp_payment' => in_array('whatsapp', OrderService::paymentMethods(), true),
            // the methods to offer, in order: card, cash (on delivery or pickup), whatsapp
            'payment_methods' => OrderService::paymentMethods(),
            // false while the owner has paused new orders from the admin
            'taking_orders' => ! \App\Models\Setting::ordersPaused(),
            // ordering hours in Abu Dhabi time, and how far ahead banquets can be booked
            'hours' => OrderService::hours(),
            'hero_video' => asset('images/brand/hero.mp4'),
            'logo' => asset('images/brand/logo.png'),
            'instagram' => 'https://instagram.com/rabeeemaratmandi',
        ];
    }

    public function branches()
    {
        return Branch::active()->get()->map(fn ($b) => [
            'id' => $b->id,
            'slug' => $b->slug,
            'name' => ['ar' => $b->name_ar, 'en' => $b->name_en],
            'address' => ['ar' => $b->address_ar, 'en' => $b->address_en],
            'phones' => $b->phones,
            'whatsapp' => $b->whatsapp,
            'map_url' => $b->map_url,
            'delivery_fee' => (float) $b->delivery_fee,
        ]);
    }

    public function menu()
    {
        return Category::active()->with('activeItems.options', 'activeItems.addonGroups.addons')->get()->map(fn ($c) => [
            'id' => $c->id,
            'slug' => $c->slug,
            'name' => ['ar' => $c->name_ar, 'en' => $c->name_en],
            'note' => ['ar' => $c->note_ar, 'en' => $c->note_en],
            'items' => $c->activeItems->map->toApi()->values(),
        ]);
    }
}
