<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home()
    {
        return view('site.home', [
            'featured' => Item::with('options')->where('is_active', true)->where('is_featured', true)->orderBy('category_id')->get(),
            'categories' => Category::active()->get(),
            'branches' => Branch::active()->get(),
        ]);
    }

    public function menu()
    {
        return view('site.menu', [
            'categories' => Category::active()->with(['activeItems.options'])->get(),
            'branches' => Branch::active()->get(),
        ]);
    }

    public function branches()
    {
        return view('site.branches', ['branches' => Branch::active()->get()]);
    }

    public function checkout()
    {
        return view('site.checkout', [
            'branches' => Branch::active()->get(),
            'cardEnabled' => OrderService::cardEnabled(),
        ]);
    }

    public function placeOrder(Request $request, OrderService $orders)
    {
        $data = $request->validate(OrderService::rules());
        $order = $orders->create($data, 'web');

        $redirect = $order->payment_method === 'card'
            ? $orders->stripeCheckoutUrl($order)
            : $orders->whatsappUrl($order);

        return response()->json(['order' => $order->code, 'redirect' => $redirect, 'status_url' => route('order.show', $order)]);
    }

    public function order(Order $order, OrderService $orders)
    {
        $order->load('items', 'branch');
        return view('site.order', [
            'order' => $order,
            'whatsapp' => $orders->whatsappUrl($order),
        ]);
    }
}
