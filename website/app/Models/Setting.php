<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Small key/value store for what the owner changes from the admin (hours, payment methods, pause). */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    /** Restaurant config keys the admin settings page can override. */
    public const HOURS = ['open', 'last', 'meal_minutes', 'book_days'];

    /** Payment methods the owner can switch on and off. Card also needs the Stripe key. */
    public const PAYMENTS = ['cash', 'whatsapp', 'card'];

    private static ?array $cache = null;

    private static function values(): array
    {
        if (static::$cache === null) {
            try {
                static::$cache = static::query()->pluck('value', 'key')->map(fn ($v) => json_decode($v, true))->all();
            } catch (\Throwable) {
                static::$cache = []; // table not migrated yet
            }
        }
        return static::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, static::values()) ? static::values()[$key] : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        static::$cache = null;
        if (str_starts_with($key, 'hours.')) static::applyToConfig();
    }

    public static function forget(): void
    {
        static::$cache = null;
    }

    /** The owner paused new orders from the admin (kitchen too busy). */
    public static function ordersPaused(): bool
    {
        return (bool) static::get('orders_paused', false);
    }

    /** Copy the owner's hours into config('restaurant.*') so the ordering rules use them. */
    public static function applyToConfig(): void
    {
        foreach (self::HOURS as $k) {
            $v = static::get('hours.'.$k);
            if ($v !== null && $v !== '') config(['restaurant.'.$k => $v]);
        }
    }
}
