<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The first lists of delivery areas per branch (the owner edits them later in admin):
 * Al Danah serves Abu Dhabi city, New Shahamah serves the areas around Shahamah.
 */
final class DeliveryAreas
{
    public const AREAS = [
        'al-danah' => [
            ['الدانة', 'Al Danah'], ['الخالدية', 'Al Khalidiyah'], ['البطين', 'Al Bateen'], ['المنهل', 'Al Manhal'],
            ['المركزية', 'Al Markaziyah'], ['الزاهية (النادي السياحي)', 'Al Zahiyah (Tourist Club)'], ['النهيان', 'Al Nahyan'],
            ['المشرف', 'Al Mushrif'], ['الكرامة', 'Al Karamah'], ['المرور', 'Al Muroor'], ['الوحدة', 'Al Wahda'],
            ['مدينة زايد', 'Madinat Zayed'], ['الحصن', 'Al Hosn'], ['الخبيرات', 'Al Khubeirat'], ['الروضة', 'Al Rawdah'],
            ['الكورنيش', 'Corniche'], ['جزيرة الريم', 'Al Reem Island'], ['جزيرة الماريه', 'Al Maryah Island'], ['السعديات', 'Saadiyat'],
        ],
        'new-shahamah' => [
            ['الشهامة الجديدة', 'New Shahamah'], ['الشهامة', 'Al Shahamah'], ['الباهية', 'Al Bahia'], ['الرحبة', 'Al Rahba'],
            ['السمحة', 'Al Samha'], ['الشامخة', 'Al Shamkha'], ['الفلاح', 'Al Falah'], ['الوثبة', 'Al Wathba'], ['جزيرة ياس', 'Yas Island'],
        ],
    ];

    // approximate branch spots, to pick the nearest branch for a map pin; the owner can correct them in admin
    public const SPOTS = ['al-danah' => [24.4890, 54.3560], 'new-shahamah' => [24.5300, 54.6700]];

    /** Add the lists to branches that have no areas yet (safe to run again). */
    public static function seed(): void
    {
        foreach (self::AREAS as $slug => $areas) {
            $branch = DB::table('branches')->where('slug', $slug)->first();
            if (! $branch) continue;
            if ($branch->lat === null) {
                [$lat, $lng] = self::SPOTS[$slug];
                DB::table('branches')->where('id', $branch->id)->update(['lat' => $lat, 'lng' => $lng]);
            }
            if (DB::table('areas')->where('branch_id', $branch->id)->exists()) continue;
            foreach ($areas as $i => [$ar, $en]) {
                DB::table('areas')->insert(['branch_id' => $branch->id, 'name_ar' => $ar, 'name_en' => $en, 'sort' => $i, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
