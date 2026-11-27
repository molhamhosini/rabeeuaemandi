<?php

namespace App\Models;

use App\Support\Localized;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use Localized;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean', 'price' => 'decimal:2'];

    public function category() { return $this->belongsTo(Category::class); }

    public function options() { return $this->hasMany(ItemOption::class)->orderBy('sort'); }

    public function imageUrl(): ?string
    {
        if (! $this->image) return null;
        return str_starts_with($this->image, 'http') ? $this->image : asset($this->image);
    }

    /** Lowest price shown on the card ("from 1225"). */
    public function displayPrice(): ?float
    {
        if ($this->price !== null) return (float) $this->price;
        $min = $this->options->min('price');
        return $min !== null ? (float) $min : null;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => ['ar' => $this->name_ar, 'en' => $this->name_en],
            'description' => ['ar' => $this->description_ar, 'en' => $this->description_en],
            'price' => $this->price !== null ? (float) $this->price : null,
            'image' => $this->imageUrl(),
            'featured' => $this->is_featured,
            'options' => $this->options->map(fn ($o) => [
                'id' => $o->id, 'name' => ['ar' => $o->name_ar, 'en' => $o->name_en], 'price' => (float) $o->price,
            ])->values(),
        ];
    }
}
