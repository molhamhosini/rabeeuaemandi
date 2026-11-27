<?php

namespace App\Models;

use App\Support\Localized;
use Illuminate\Database\Eloquent\Model;

class ItemAddon extends Model
{
    use Localized;

    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2'];

    public function group() { return $this->belongsTo(ItemAddonGroup::class, 'item_addon_group_id'); }
}
