<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = ['subtotal' => 'decimal:2', 'delivery_fee' => 'decimal:2', 'total' => 'decimal:2'];

    public function branch() { return $this->belongsTo(Branch::class); }

    public function items() { return $this->hasMany(OrderItem::class); }

    public function getRouteKeyName(): string { return 'code'; }

    public function toApi(): array
    {
        return [
            'code' => $this->code,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'fulfillment' => $this->fulfillment,
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'total' => (float) $this->total,
            'branch' => ['id' => $this->branch_id, 'name' => ['ar' => $this->branch->name_ar, 'en' => $this->branch->name_en]],
            'items' => $this->items->map(fn ($i) => [
                'name' => ['ar' => $i->name_ar, 'en' => $i->name_en], 'unit_price' => (float) $i->unit_price,
                'quantity' => $i->quantity, 'line_total' => (float) $i->line_total,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
