<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request, OrderService $orders)
    {
        $data = $request->validate(OrderService::rules());
        // a signed-in customer's order goes to their account, and their name and address are kept for next time
        $customer = $request->attributes->get('customer');
        $order = $orders->create($data + ['customer_id' => $customer?->id], 'app');
        if ($customer) {
            $customer->update(['name' => $customer->name ?: $data['customer_name'], 'branch_id' => $data['branch_id']]
                // a delivery replaces the saved place (address, area and map pin) with this one
                + ($data['fulfillment'] === 'delivery'
                    ? ['address' => $data['address'] ?? null, 'area_id' => $order->area_id, 'lat' => $data['lat'] ?? null, 'lng' => $data['lng'] ?? null]
                    : []));
        }

        return response()->json([
            'order' => $order->toApi(),
            'redirect' => $order->payment_method === 'cash' ? null : $orders->redirectFor($order),
        ], 201);
    }

    public function show(Order $order)
    {
        return $order->load('items', 'branch')->toApi();
    }
}
