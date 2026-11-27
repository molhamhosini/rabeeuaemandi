<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        if (! $secret || ! OrderService::verifyStripeSignature($payload, $request->header('Stripe-Signature'), $secret)) {
            return response('invalid signature', 400);
        }

        $event = json_decode($payload, true);
        $session = $event['data']['object'] ?? [];
        $order = Order::where('stripe_session_id', $session['id'] ?? '')->first();

        if ($order) {
            match ($event['type'] ?? '') {
                'checkout.session.completed' => ($session['payment_status'] ?? '') === 'paid'
                    ? $order->update(['payment_status' => 'paid', 'status' => $order->status === 'new' ? 'confirmed' : $order->status])
                    : null,
                'checkout.session.async_payment_succeeded' => $order->update(['payment_status' => 'paid']),
                'checkout.session.async_payment_failed', 'checkout.session.expired' => $order->update(['payment_status' => 'failed']),
                default => null,
            };
        }

        return response('ok');
    }
}
