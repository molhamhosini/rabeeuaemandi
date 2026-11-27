<?php

namespace App\Models;

use App\Support\Localized;
use Illuminate\Database\Eloquent\Model;

class ItemAddonGroup extends Model
{
    use Localized;

    protected $guarded = [];

    protected $casts = ['multiple' => 'boolean', 'required' => 'boolean'];

    public function item() { return $this->belongsTo(Item::class); }

    public function addons() { return $this->hasMany(ItemAddon::class)->orderBy('sort'); }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'name' => ['ar' => $this->name_ar, 'en' => $this->name_en],
            'multiple' => $this->multiple,
            'required' => $this->required,
            'addons' => $this->addons->map(fn ($a) => [
                'id' => $a->id, 'name' => ['ar' => $a->name_ar, 'en' => $a->name_en], 'price' => (float) $a->price,
            ])->values(),
        ];
    }
}
