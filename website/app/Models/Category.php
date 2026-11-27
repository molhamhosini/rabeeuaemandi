<?php

namespace App\Models;

use App\Support\Localized;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Localized;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function items() { return $this->hasMany(Item::class)->orderBy('sort'); }

    public function activeItems() { return $this->items()->where('is_active', true); }

    public function scopeActive($q) { return $q->where('is_active', true)->orderBy('sort'); }
}
