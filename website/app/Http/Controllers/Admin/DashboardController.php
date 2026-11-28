<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderService;

class DashboardController extends Controller
{
    public function index()
    {
        // days are counted in Abu Dhabi time, not the server's UTC
        $now = now(OrderService::TZ);
        $todayStart = $now->copy()->startOfDay()->utc();
        $yesterdayStart = $now->copy()->subDay()->startOfDay()->utc();
        $sold = fn () => Order::where('status', '!=', 'cancelled');

        $today = $sold()->where('created_at', '>=', $todayStart)->get(['id', 'total', 'branch_id']);
        $yesterday = $sold()->whereBetween('created_at', [$yesterdayStart, $todayStart])->get(['total']);

        // sales per day for the chart, oldest first
        $from = $now->copy()->subDays(13)->startOfDay();
        $perDay = $sold()->where('created_at', '>=', $from->copy()->utc())->get(['created_at', 'total'])
            ->groupBy(fn ($o) => $o->created_at->copy()->timezone(OrderService::TZ)->format('Y-m-d'));
        $chart = [];
        for ($d = $from->copy(); $d->lte($now); $d->addDay()) {
            $rows = $perDay->get($d->format('Y-m-d'), collect());
            $chart[] = ['day' => $d->copy(), 'total' => (float) $rows->sum('total'), 'count' => $rows->count()];
        }

        $top = OrderItem::whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled')->where('created_at', '>=', now()->subDays(7)))
            ->selectRaw('item_id, SUM(quantity) as qty, SUM(line_total) as sales')
            ->groupBy('item_id')->orderByDesc('qty')->limit(5)->get();
        $items = Item::whereIn('id', $top->pluck('item_id'))->get()->keyBy('id');

        return view('admin.dashboard', [
            'now' => $now,
            'stats' => [
                'count' => $today->count(),
                'sales' => (float) $today->sum('total'),
                'avg' => $today->count() ? (float) $today->avg('total') : 0,
                'y_count' => $yesterday->count(),
                'y_sales' => (float) $yesterday->sum('total'),
                'new' => Order::where('status', 'new')->count(),
                'active' => Order::whereIn('status', ['confirmed', 'preparing', 'out_for_delivery'])->count(),
            ],
            'chart' => $chart,
            'top' => $top->map(fn ($r) => ['item' => $items->get($r->item_id), 'qty' => (int) $r->qty, 'sales' => (float) $r->sales])->filter(fn ($r) => $r['item']),
            'branches' => Branch::orderBy('sort')->get()->map(fn ($b) => [
                'name' => $b->tr('name'),
                'count' => $today->where('branch_id', $b->id)->count(),
                'sales' => (float) $today->where('branch_id', $b->id)->sum('total'),
            ]),
            'upcoming' => Order::with('branch')->whereNotNull('scheduled_at')
                ->where('scheduled_at', '>=', now()->subHour())->where('scheduled_at', '<=', now()->addDays(7))
                ->whereNotIn('status', ['cancelled', 'completed'])->orderBy('scheduled_at')->limit(8)->get(),
            'fresh' => Order::with('branch')->where('status', 'new')->latest('id')->limit(6)->get(),
        ]);
    }

    /** Polled by every admin page: how many new orders, and the newest id, to ring for new ones. */
    public function poll()
    {
        $en = app()->getLocale() === 'en';
        $new = Order::with('items', 'branch')->where('status', 'new')->oldest('id')->limit(5)->get();
        return response()->json([
            'new' => Order::where('status', 'new')->count(),
            'latest' => (int) Order::max('id'),
            'paused' => \App\Models\Setting::ordersPaused(),
            // the new-order window shows these with accept / reject
            'orders' => $new->map(fn (Order $o) => [
                'id' => $o->id,
                'number' => $o->number,
                'customer' => $o->customer_name,
                'phone' => $o->customer_phone,
                'branch' => $o->branch->tr('name'),
                'fulfillment' => __('admin.'.($o->fulfillment === 'delivery' ? 'delivery' : 'pickup')),
                'when' => $o->whenText(),
                'total' => OrderService::money($o->total),
                'items' => $o->items->map(fn ($i) => $i->quantity.' × '.($en ? $i->name_en : $i->name_ar)
                    .(($d = $en ? $i->details_en : $i->details_ar) ? ' ('.$d.')' : ''))->all(),
                'notes' => $o->notes,
                'update' => route('admin.orders.update', $o),
                'url' => route('admin.orders.show', $o),
            ]),
        ]);
    }
}
