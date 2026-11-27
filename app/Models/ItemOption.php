<?php

namespace App\Models;

use App\Support\Localized;
use Illuminate\Database\Eloquent\Model;

class ItemOption extends Model
{
    use Localized;

    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2'];

    public function item() { return $this->belongsTo(Item::class); }
}
