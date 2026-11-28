<?php

namespace App\Models;

use App\Support\Localized;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use Localized;

    protected $guarded = [];

    protected $casts = ['phones' => 'array', 'is_active' => 'boolean', 'delivery_fee' => 'decimal:2', 'min_order' => 'decimal:2'];

    public function areas() { return $this->hasMany(Area::class)->orderBy('sort')->orderBy('id'); }

    public function scopeActive($q) { return $q->where('is_active', true)->orderBy('sort'); }
}
