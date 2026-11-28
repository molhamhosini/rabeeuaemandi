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
        $order = $orders->create($data, 'app');

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
