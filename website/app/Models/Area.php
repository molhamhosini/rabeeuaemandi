<?php

namespace App\Models;

use App\Support\Localized;
use Illuminate\Database\Eloquent\Model;

/** A delivery area (e.g. Al Khalidiyah); it belongs to the branch that delivers there. */
class Area extends Model
{
    use Localized;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function branch() { return $this->belongsTo(Branch::class); }

    public function scopeActive($q) { return $q->where('is_active', true)->orderBy('sort')->orderBy('id'); }
}
