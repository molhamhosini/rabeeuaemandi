<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public static function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s-]{7,20}$/'],
            'fulfillment' => 'required|in:delivery,pickup',
            'address' => 'required_if:fulfillment,delivery|nullable|string|max:300',
            'notes' => 'nullable|string|max:500',
            // local Abu Dhabi time from the form, e.g. 2026-11-28T19:30; empty = as soon as possible
            'scheduled_at' => 'nullable|date_format:Y-m-d\TH:i',
            'payment_method' => 'required|in:card,whatsapp',
            'items' => 'required|array|min:1|max:50',
            'items.*.item_id' => 'required|integer|exists:items,id',
            'items.*.option_id' => 'nullable|integer|exists:item_options,id',
            'items.*.addon_ids' => 'nullable|array|max:30',
            'items.*.addon_ids.*' => 'integer',
            'items.*.quantity' => 'required|integer|min:1|max:50',
        ];
    }

    public const TZ = 'Asia/Dubai'; // same as config('restaurant.timezone')

    /** Ordering hours for the site and the app: {open, last, step, days}. */
    public static function hours(): array
    {
        return [
            'open' => config('restaurant.open'), 'last' => config('restaurant.last'),
            'step' => (int) config('restaurant.slot_minutes'), 'days' => (int) config('restaurant.book_days'),
            'meal' => (int) config('restaurant.meal_minutes'),
        ];
    }

    /** "12:00 PM to 11:30 PM" in the given language. */
    public static function hoursText(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $f = fn ($t) => Carbon::createFromFormat('H:i', $t, self::TZ)->locale($locale)->translatedFormat('g:i A');
        return __('site.schedule.hours', ['open' => $f(config('restaurant.open')), 'last' => $f(config('restaurant.last'))], $locale);
    }

    /**
     * Check the wanted time: inside ordering hours, on a 30-minute step, the same day for
     * ordinary dishes, and with enough notice for banquets and the cooking service (3 hours).
     * Returns the time in UTC, or null for "as soon as possible".
     */
    private function schedule(?string $at, int $lead): ?Carbon
    {
        $fail = fn (string $key) => throw ValidationException::withMessages(['scheduled_at' => __('site.schedule.'.$key, ['hours' => $lead, 'range' => self::hoursText()])]);
        $now = now(self::TZ);
        $open = $now->copy()->setTimeFromTimeString(config('restaurant.open'));
        $last = $now->copy()->setTimeFromTimeString(config('restaurant.last'));

        if (! $at) {
            if ($lead > 0) $fail('required');
            if ($now->lt($open) || $now->gt($last)) $fail('closed_now');
            return null;
        }

        $when = Carbon::createFromFormat('Y-m-d\TH:i', $at, self::TZ)->second(0);
        $minutes = $when->hour * 60 + $when->minute;
        [$oh, $om] = array_map('intval', explode(':', config('restaurant.open')));
        [$lh, $lm] = array_map('intval', explode(':', config('restaurant.last')));
        if ($minutes < $oh * 60 + $om || $minutes > $lh * 60 + $lm || $minutes % (int) config('restaurant.slot_minutes') !== 0) {
            $fail('bad_slot');
        }
        if ($lead === 0 && ! $when->isSameDay($now)) $fail('same_day');
        // a few minutes of slack for the time the customer spent on the form
        $notice = $lead > 0 ? $lead * 60 : (int) config('restaurant.meal_minutes');
        if ($when->lt($now->copy()->addMinutes($notice)->subMinutes(10))) $fail($lead > 0 ? 'too_soon' : 'past');
        if ($when->gt($now->copy()->addDays((int) config('restaurant.book_days'))->endOfDay())) $fail('too_far');

        return $when->utc();
    }

    public static function cardEnabled(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    /** Create the order with prices taken from the database, never from the client. */
    public function create(array $data, string $source = 'web'): Order
    {
        if ($data['payment_method'] === 'card' && ! self::cardEnabled()) {
            throw ValidationException::withMessages(['payment_method' => __('site.card_unavailable')]);
        }

        $branch = Branch::where('is_active', true)->findOrFail($data['branch_id']);

        return DB::transaction(function () use ($data, $branch, $source) {
            $lines = [];
            $subtotal = 0;
            $lead = 0;
            foreach ($data['items'] as $row) {
                $item = Item::with('options', 'addonGroups.addons', 'category')->where('is_active', true)->findOrFail($row['item_id']);
                $lead = max($lead, (int) $item->category?->lead_hours);
                $option = null;
                if ($item->options->isNotEmpty()) {
                    $option = $item->options->firstWhere('id', (int) ($row['option_id'] ?? 0));
                    if (! $option) {
                        throw ValidationException::withMessages(['items' => __('site.choose_option', ['item' => $item->name_ar])]);
                    }
                }
                [$addons, $extra, $detailsAr, $detailsEn] = $this->pickAddons($item, $row['addon_ids'] ?? []);
                $unit = (float) ($option?->price ?? $item->price) + $extra;
                $qty = (int) $row['quantity'];
                $subtotal += $unit * $qty;
                $lines[] = [
                    'item_id' => $item->id,
                    'item_option_id' => $option?->id,
                    'addons' => $addons ?: null,
                    'name_ar' => $item->name_ar.($option ? ' - '.$option->name_ar : ''),
                    'name_en' => $item->name_en.($option ? ' - '.$option->name_en : ''),
                    'details_ar' => $detailsAr ?: null,
                    'details_en' => $detailsEn ?: null,
                    'unit_price' => $unit,
                    'quantity' => $qty,
                    'line_total' => $unit * $qty,
                ];
            }

            $scheduled = $this->schedule($data['scheduled_at'] ?? null, $lead);
            $fee = $data['fulfillment'] === 'delivery' ? (float) $branch->delivery_fee : 0;

            $order = Order::create([
                'code' => strtoupper(Str::random(3)).now()->format('dHis'),
                'branch_id' => $branch->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'fulfillment' => $data['fulfillment'],
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'scheduled_at' => $scheduled,
                'subtotal' => $subtotal,
                'delivery_fee' => $fee,
                'total' => $subtotal + $fee,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_method'] === 'card' ? 'pending' : 'unpaid',
                'source' => $source,
                'locale' => app()->getLocale(),
            ]);
            $order->items()->createMany($lines);

            return $order->load('items', 'branch');
        });
    }

    /**
     * Check the customer's extra choices (rice type, serving, extras) against the dish
     * and price them from the database.
     *
     * @return array{0: array, 1: float, 2: string, 3: string}
     */
    private function pickAddons(Item $item, array $ids): array
    {
        // an id may repeat: "two extra chutney" arrives as the chutney id twice
        $counts = collect($ids)->map(fn ($v) => (int) $v)->countBy();
        $valid = $item->addonGroups->flatMap->addons->pluck('id');
        if ($counts->keys()->diff($valid)->isNotEmpty()) {
            throw ValidationException::withMessages(['items' => __('site.bad_addon', ['item' => $item->name_ar])]);
        }

        $chosen = [];
        $extra = 0.0;
        $ar = [];
        $en = [];
        foreach ($item->addonGroups as $group) {
            $sel = $group->addons->filter(fn ($a) => $counts->has($a->id));
            if ($group->required && $sel->isEmpty()) {
                throw ValidationException::withMessages(['items' => __('site.choose_addon', ['group' => $group->name_ar, 'item' => $item->name_ar])]);
            }
            if (! $group->multiple && $sel->count() > 1) {
                throw ValidationException::withMessages(['items' => __('site.bad_addon', ['item' => $item->name_ar])]);
            }
            if ($sel->isEmpty()) continue;
            $partsAr = [];
            $partsEn = [];
            foreach ($sel as $a) {
                $n = $counts[$a->id];
                // only paid extras in a multi-choice group can be counted (2 chutney); a rice type is picked once
                if ($n > 1 && (! $group->multiple || (float) $a->price <= 0 || $n > 10)) {
                    throw ValidationException::withMessages(['items' => __('site.bad_addon', ['item' => $item->name_ar])]);
                }
                $chosen[] = ['id' => $a->id, 'group_ar' => $group->name_ar, 'group_en' => $group->name_en,
                    'name_ar' => $a->name_ar, 'name_en' => $a->name_en, 'price' => (float) $a->price, 'qty' => $n];
                $extra += (float) $a->price * $n;
                $partsAr[] = $a->name_ar.($n > 1 ? ' ×'.$n : '');
                $partsEn[] = $a->name_en.($n > 1 ? ' ×'.$n : '');
            }
            $ar[] = $group->name_ar.': '.implode('، ', $partsAr);
            $en[] = $group->name_en.': '.implode(', ', $partsEn);
        }

        return [$chosen, $extra, implode(' · ', $ar), implode(' · ', $en)];
    }

    /** wa.me link that opens WhatsApp to the branch with the order already written out. */
    public function whatsappUrl(Order $order): string
    {
        $ar = $order->locale !== 'en';
        $lines = [];
        $lines[] = $ar ? "طلب جديد #{$order->code}" : "New order #{$order->code}";
        $lines[] = ($ar ? 'الفرع: ' : 'Branch: ').($ar ? $order->branch->name_ar : $order->branch->name_en);
        $lines[] = ($ar ? 'الموعد: ' : 'Time: ').$order->whenText($ar ? 'ar' : 'en');
        $lines[] = '';
        foreach ($order->items as $i) {
            $lines[] = "{$i->quantity} × ".($ar ? $i->name_ar : $i->name_en).' = '.self::money($i->line_total);
            $details = $ar ? $i->details_ar : $i->details_en;
            if ($details) {
                foreach (explode(' · ', $details) as $d) $lines[] = '   - '.$d;
            }
        }
        $lines[] = '';
        $lines[] = ($ar ? 'الإجمالي: ' : 'Total: ').self::money($order->total).' AED';
        $lines[] = ($ar ? 'الاسم: ' : 'Name: ').$order->customer_name;
        $lines[] = ($ar ? 'الهاتف: ' : 'Phone: ').$order->customer_phone;
        $lines[] = $order->fulfillment === 'delivery'
            ? ($ar ? 'توصيل إلى: ' : 'Deliver to: ').$order->address
            : ($ar ? 'استلام من المطعم' : 'Pickup from restaurant');
        if ($order->notes) $lines[] = ($ar ? 'ملاحظات: ' : 'Notes: ').$order->notes;

        return 'https://wa.me/'.$order->branch->whatsapp.'?text='.rawurlencode(implode("\n", $lines));
    }

    /** Stripe Checkout session (hosted payment page: cards, Apple Pay, Google Pay). */
    public function stripeCheckoutUrl(Order $order): string
    {
        $currency = config('services.stripe.currency', 'aed');
        $ar = $order->locale !== 'en';
        $form = [
            'mode' => 'payment',
            'locale' => $ar ? 'auto' : 'en',
            'client_reference_id' => $order->code,
            'metadata[order_code]' => $order->code,
            'success_url' => route('order.show', $order).'?paid=1',
            'cancel_url' => route('order.show', $order).'?cancelled=1',
            'phone_number_collection[enabled]' => 'false',
        ];
        $n = 0;
        foreach ($order->items as $i) {
            $form["line_items[$n][quantity]"] = $i->quantity;
            $form["line_items[$n][price_data][currency]"] = $currency;
            $form["line_items[$n][price_data][unit_amount]"] = (int) round($i->unit_price * 100);
            $form["line_items[$n][price_data][product_data][name]"] = $ar ? $i->name_ar : $i->name_en;
            if ($details = $ar ? $i->details_ar : $i->details_en) {
                $form["line_items[$n][price_data][product_data][description]"] = mb_substr($details, 0, 500);
            }
            $n++;
        }
        if ($order->delivery_fee > 0) {
            $form["line_items[$n][quantity]"] = 1;
            $form["line_items[$n][price_data][currency]"] = $currency;
            $form["line_items[$n][price_data][unit_amount]"] = (int) round($order->delivery_fee * 100);
            $form["line_items[$n][price_data][product_data][name]"] = $ar ? 'رسوم التوصيل' : 'Delivery fee';
        }

        $res = Http::asForm()->withToken(config('services.stripe.secret'))
            ->post('https://api.stripe.com/v1/checkout/sessions', $form)->throw()->json();

        $order->update(['stripe_session_id' => $res['id']]);

        return $res['url'];
    }

    /** Verify a Stripe webhook signature (Stripe-Signature: t=...,v1=...). */
    public static function verifyStripeSignature(string $payload, ?string $header, string $secret, int $tolerance = 300): bool
    {
        if (! $header) return false;
        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', $kv, 2), 2, null);
            $parts[$k][] = $v;
        }
        $t = (int) ($parts['t'][0] ?? 0);
        if (! $t || abs(time() - $t) > $tolerance) return false;
        $expected = hash_hmac('sha256', $t.'.'.$payload, $secret);
        foreach ($parts['v1'] ?? [] as $sig) {
            if (hash_equals($expected, (string) $sig)) return true;
        }
        return false;
    }

    public static function money($v): string
    {
        $v = (float) $v;
        return fmod($v, 1.0) == 0.0 ? number_format($v, 0) : number_format($v, 2);
    }
}
