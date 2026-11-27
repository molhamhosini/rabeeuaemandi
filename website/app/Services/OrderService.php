<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\Order;
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
            'payment_method' => 'required|in:card,whatsapp',
            'items' => 'required|array|min:1|max:50',
            'items.*.item_id' => 'required|integer|exists:items,id',
            'items.*.option_id' => 'nullable|integer|exists:item_options,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
        ];
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
            foreach ($data['items'] as $row) {
                $item = Item::with('options')->where('is_active', true)->findOrFail($row['item_id']);
                $option = null;
                if ($item->options->isNotEmpty()) {
                    $option = $item->options->firstWhere('id', (int) ($row['option_id'] ?? 0));
                    if (! $option) {
                        throw ValidationException::withMessages(['items' => __('site.choose_option', ['item' => $item->name_ar])]);
                    }
                }
                $unit = (float) ($option?->price ?? $item->price);
                $qty = (int) $row['quantity'];
                $subtotal += $unit * $qty;
                $lines[] = [
                    'item_id' => $item->id,
                    'item_option_id' => $option?->id,
                    'name_ar' => $item->name_ar.($option ? ' - '.$option->name_ar : ''),
                    'name_en' => $item->name_en.($option ? ' - '.$option->name_en : ''),
                    'unit_price' => $unit,
                    'quantity' => $qty,
                    'line_total' => $unit * $qty,
                ];
            }

            $fee = $data['fulfillment'] === 'delivery' ? (float) $branch->delivery_fee : 0;

            $order = Order::create([
                'code' => strtoupper(Str::random(3)).now()->format('dHis'),
                'branch_id' => $branch->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'fulfillment' => $data['fulfillment'],
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
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

    /** wa.me link that opens WhatsApp to the branch with the order already written out. */
    public function whatsappUrl(Order $order): string
    {
        $ar = $order->locale !== 'en';
        $lines = [];
        $lines[] = $ar ? "طلب جديد #{$order->code}" : "New order #{$order->code}";
        $lines[] = ($ar ? 'الفرع: ' : 'Branch: ').($ar ? $order->branch->name_ar : $order->branch->name_en);
        $lines[] = '';
        foreach ($order->items as $i) {
            $lines[] = "{$i->quantity} × ".($ar ? $i->name_ar : $i->name_en).' = '.self::money($i->line_total);
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
