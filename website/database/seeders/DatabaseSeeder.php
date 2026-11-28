<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /** Real photos the owner sent (2026-11-28) for dishes that have no rice question. Meals with rice keep the
     *  printed-menu (PSD) picture; the owner's plates without rice go on their "without rice" twins below. */
    public const PHOTOS = [
        'Yogurt Normal' => 'yogurt', 'Yogurt Cucumber' => 'yogurt', 'Cold Drinks' => 'cold-drinks',
        // the owner's fish photos show the fish with its rice plate, so they suit the meal with rice
        'Suprem Fish' => 'fish-suprem-plate', 'Hamour Fish' => 'fish-hamour-plate', 'Salmon Fish' => 'fish-salmon-plate',
    ];

    /** Dishes whose photo PHOTOS used to replace with a plate without rice (undone by migration 000700). */
    public const RICE_PSD_RESTORED = [
        'Suprem Fish', 'Hamour Fish', 'Salmon Fish', 'Madfoon Lamb', 'Madhbi Lamb', 'Zirbyan Lamb', 'Burma Lamb',
        'Head with rice', 'Mukalkal Lamb', 'Mandi Chicken', 'Madhbi Chicken', 'Madfoon Chicken',
    ];

    /** Photos for the "without rice" dishes: the same dish with the rice plate taken out, or, where the meat
     *  sits on the rice in the only photo, a plate of the same meat without rice. */
    public const NO_RICE_PHOTOS = [
        'Suprem Fish' => 'fish-suprem-norice', 'Hamour Fish' => 'fish-hamour-norice', 'Salmon Fish' => 'fish-salmon-norice',
        'Mukalkal Lamb' => 'lamb-mukalkal-norice', 'Burma Chicken' => 'chicken-burma-norice',
        'Madfoon Lamb' => 'lamb-madfoon-plate', 'Madhbi Lamb' => 'lamb-madhbi-plate', 'Zirbyan Lamb' => 'lamb-zirbyan-plate',
        'Burma Lamb' => 'lamb-burma-plate',
        'Mandi Chicken' => 'chicken-mandi-plate', 'Madhbi Chicken' => 'chicken-madhbi-plate', 'Madfoon Chicken' => 'chicken-madfoon-plate',
        'Mandi Lamb' => 'lamb-madhbi-plate', 'Bukhari Lamb' => 'lamb-madhbi-plate', 'Hashi Lamb' => 'lamb-madhbi-plate',
        'Haneez Lamb' => 'lamb-madhbi-plate', 'Tanoor Lamb' => 'lamb-madhbi-plate',
        'Bukhari Chicken' => 'chicken-mandi-plate', 'Haneeth Chicken' => 'chicken-mandi-plate',
    ];

    /** Lamb, chicken and fish meals also sold without rice, 8 AED less (the price of a rice plate). */
    public const NO_RICE_SECTIONS = ['lamb', 'chicken', 'fish'];
    public const NO_RICE_SKIP = ['Waslah Lamb', 'Head with rice', 'Harees Lamb', 'Whole Stuffed Chicken'];
    public const NO_RICE_DISCOUNT = 8;

    /** The dish and, where it is sold both ways, its "without rice" twin right after it. */
    public static function withNoRice(string $section, array $items): array
    {
        $out = [];
        foreach ($items as $i) {
            $out[] = $i;
            if (in_array($section, self::NO_RICE_SECTIONS, true) && empty($i['options']) && $i['price'] !== null
                && ! in_array($i['name_en'], self::NO_RICE_SKIP, true)) {
                $out[] = ['name_ar' => $i['name_ar'].' بدون رز', 'name_en' => $i['name_en'].' (no rice)',
                    'price' => max(0, $i['price'] - self::NO_RICE_DISCOUNT), 'image' => $i['image'] ?? null,
                    'featured' => false, 'options' => [], 'no_rice' => true,
                    'description_ar' => $i['description_ar'] ?? null, 'description_en' => $i['description_en'] ?? null,
                    'photo_of' => $i['name_en']];
            }
        }
        return $out;
    }

    /** Dish images come from the printed-menu PSDs, all on one shared background (images/menu). */
    private function image(?string $slug): string
    {
        $path = 'images/menu/'.($slug ?: 'emblem').'.webp';
        return file_exists(public_path($path)) ? $path : 'images/menu/emblem.webp';
    }

    /** Rice types a customer can ask for (from the rice section of the menu). Picking two or more makes it mixed. */
    private const RICE = [
        ['مندي', 'Mandi'], ['بخاري', 'Bukhari'], ['زربيان', 'Zurbian'], ['كبسة', 'Kabsa'], ['بشاور', 'Beshawer'],
    ];

    /** Extras. Yogurt is priced like "plain yogurt" in drinks; the owner sets the rest from the admin panel. */
    private const EXTRAS = [
        ['شتني إضافي', 'Extra chutney', 2], ['روب إضافي', 'Extra yogurt', 2], ['سلطة إضافية', 'Extra salad', 16],
    ];

    /** Extra choices per menu section: [name_ar, name_en, multiple, required, [[ar, en, price], ...]]. */
    private function addonGroups(string $category, array $item): array
    {
        $rice = fn (bool $required) => ['نوع الرز', 'Rice type',
            true, $required, array_map(fn ($r) => [$r[0], $r[1], 0], self::RICE)];
        $extras = ['إضافات', 'Extras', true, false, self::EXTRAS];

        if (! empty($item['no_rice'])) return [$extras];

        return match (true) {
            $category === 'banquets' && ! str_contains($item['name_ar'], 'بدون أرز') => [
                $rice(true),
                ['طريقة التقديم', 'Serving', false, true, [
                    ['صحن واحد كبير', 'One large platter', 0],
                    ['مقسّمة على عدة صحون', 'Split over several plates', 0],
                ]],
                $extras,
            ],
            in_array($category, ['lamb', 'chicken'], true) && empty($item['options']) => [$rice(false), $extras],
            $category === 'fish' => [$rice(false), $extras],
            default => [],
        };
    }

    public function run(): void
    {
        // only creates the admin the first time; running the seeder again must never reset the password
        User::firstOrCreate(
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
                // whole lambs (banquets, cooking service) are booked 3 hours ahead, on any day
                'lead_hours' => in_array($c['slug'], ['banquets', 'cooking-service'], true) ? 3 : 0,
            ]);
            foreach (self::withNoRice($c['slug'], $c['items']) as $ii => $i) {
                $item = $category->items()->updateOrCreate(['name_en' => $i['name_en']], [
                    'name_ar' => $i['name_ar'],
                    'description_ar' => $i['description_ar'] ?? null,
                    'description_en' => $i['description_en'] ?? null,
                    'price' => $i['price'],
                    'image' => $this->image((isset($i['photo_of']) ? self::NO_RICE_PHOTOS[$i['photo_of']] ?? null : null)
                        ?? self::PHOTOS[$i['photo_of'] ?? $i['name_en']] ?? $i['image']),
                    'is_featured' => $i['featured'],
                    'sort' => $ii,
                ]);
                $item->options()->delete();
                foreach ($i['options'] as $oi => $o) {
                    $item->options()->create($o + ['sort' => $oi]);
                }
                $item->addonGroups()->delete();
                foreach ($this->addonGroups($c['slug'], $i) as $gi => [$gar, $gen, $multiple, $required, $addons]) {
                    $group = $item->addonGroups()->create([
                        'name_ar' => $gar, 'name_en' => $gen, 'multiple' => $multiple, 'required' => $required, 'sort' => $gi,
                    ]);
                    foreach ($addons as $ai => [$aar, $aen, $price]) {
                        $group->addons()->create(['name_ar' => $aar, 'name_en' => $aen, 'price' => $price, 'sort' => $ai]);
                    }
                }
            }
        }
    }
}
