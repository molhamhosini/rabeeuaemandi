<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /** Dish images come from the printed-menu PSDs, all on one shared background (images/menu). */
    private function image(?string $slug): string
    {
        $path = 'images/menu/'.($slug ?: 'emblem').'.webp';
        return file_exists(public_path($path)) ? $path : 'images/menu/emblem.webp';
    }

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@rabeeuaemandi.com')],
            ['name' => 'Admin', 'password' => Hash::make(env('ADMIN_PASSWORD', 'change-me-now')), 'is_admin' => true]
        );

        $branches = [
            [
                'slug' => 'al-danah', 'sort' => 1,
                'name_ar' => 'فرع الدانة', 'name_en' => 'Al Danah Branch',
                'address_ar' => 'أبوظبي - الدانة', 'address_en' => 'Al Danah, Abu Dhabi',
                'phones' => ['02 641 0999', '02 641 0888', '058 140 4800', '058 140 4900'],
                'whatsapp' => '971581404800',
            ],
            [
                'slug' => 'new-shahamah', 'sort' => 2,
                'name_ar' => 'فرع الشهامة الجديدة', 'name_en' => 'New Shahamah Branch',
                'address_ar' => 'أبوظبي - الشهامة الجديدة', 'address_en' => 'New Shahamah, Abu Dhabi',
                'phones' => ['055 806 6900', '055 805 5900', '02 563 9949', '02 563 3660'],
                'whatsapp' => '971558066900',
            ],
        ];
        foreach ($branches as $b) {
            Branch::updateOrCreate(['slug' => $b['slug']], $b + ['hours_ar' => 'يوميًا', 'hours_en' => 'Daily', 'delivery_fee' => 0]);
        }

        $menu = json_decode(file_get_contents(database_path('data/menu.json')), true);
        foreach ($menu['categories'] as $ci => $c) {
            $category = Category::updateOrCreate(['slug' => $c['slug']], [
                'name_ar' => $c['name_ar'], 'name_en' => $c['name_en'],
                'note_ar' => $c['note_ar'] ?? null, 'note_en' => $c['note_en'] ?? null, 'sort' => $ci,
            ]);
            foreach ($c['items'] as $ii => $i) {
                $item = $category->items()->updateOrCreate(['name_en' => $i['name_en']], [
                    'name_ar' => $i['name_ar'],
                    'description_ar' => $i['description_ar'] ?? null,
                    'description_en' => $i['description_en'] ?? null,
                    'price' => $i['price'],
                    'image' => $this->image($i['image']),
                    'is_featured' => $i['featured'],
                    'sort' => $ii,
                ]);
                $item->options()->delete();
                foreach ($i['options'] as $oi => $o) {
                    $item->options()->create($o + ['sort' => $oi]);
                }
            }
        }
    }
}
