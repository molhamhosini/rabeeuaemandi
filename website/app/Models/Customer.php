<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    protected $guarded = [];

    protected $casts = ['last_login_at' => 'datetime'];

    public function orders() { return $this->hasMany(Order::class)->latest('id'); }

    public function tokens() { return $this->hasMany(CustomerToken::class); }

    /**
     * Turn what people type (058 140 4800, +971 58..., 00971...) into 9715XXXXXXXX.
     * Returns null when it is not a UAE mobile number.
     */
    public static function normalizePhone(?string $raw): ?string
    {
        $d = preg_replace('/\D/', '', (string) $raw);
        if (str_starts_with($d, '00')) $d = substr($d, 2);
        if (str_starts_with($d, '971')) $d = substr($d, 3);
        if (str_starts_with($d, '0')) $d = substr($d, 1);
        return preg_match('/^5[0-9]{8}$/', $d) ? '971'.$d : null;
    }

    /** 058 140 4800 */
    public function getLocalPhoneAttribute(): string
    {
        $l = '0'.substr($this->phone, 3);
        return substr($l, 0, 3).' '.substr($l, 3, 3).' '.substr($l, 6);
    }

    /** Make a new sign-in for one device and hand back the plain token (shown once). */
    public function issueToken(?string $device = null): string
    {
        $plain = Str::random(60);
        $this->tokens()->create(['token_hash' => hash('sha256', $plain), 'device' => $device ? Str::limit($device, 97) : null, 'last_used_at' => now()]);
        $this->forceFill(['last_login_at' => now()])->save();
        return $plain;
    }

    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->local_phone,
            'name' => $this->name,
            'address' => $this->address,
            'area_id' => $this->area_id,
            'location' => $this->lat !== null ? ['lat' => (float) $this->lat, 'lng' => (float) $this->lng] : null,
            'branch_id' => $this->branch_id,
            'orders_count' => $this->orders()->count(),
        ];
    }
}
