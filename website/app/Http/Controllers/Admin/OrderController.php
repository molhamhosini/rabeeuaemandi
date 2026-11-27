<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('branch')
            ->when($request->branch, fn ($q, $b) => $q->where('branch_id', $b))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(30)->withQueryString();

        $today = Order::whereDate('created_at', today());

        return view('admin.orders', [
            'orders' => $orders,
            'branches' => Branch::orderBy('sort')->get(),
            'stats' => [
                'today' => (clone $today)->count(),
                'today_total' => (clone $today)->where('status', '!=', 'cancelled')->sum('total'),
                'new' => Order::where('status', 'new')->count(),
            ],
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.order', ['order' => $order->load('items', 'branch')]);
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->validate([
            'status' => 'required|in:new,confirmed,preparing,out_for_delivery,completed,cancelled',
            'payment_status' => 'nullable|in:unpaid,pending,paid,failed,refunded',
        ]));
        return back()->with('ok', __('site.admin.saved'));
    }
}
