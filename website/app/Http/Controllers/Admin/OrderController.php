<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** Tabs on the order history page and the statuses each one shows. */
    public const TABS = [
        'all' => null,
        'new' => ['new'],
        'active' => ['confirmed', 'preparing', 'out_for_delivery'],
        'scheduled' => null, // bookings with a set time, not finished
        'done' => ['completed'],
        'cancelled' => ['cancelled'],
    ];

    /** Order history: tabs by status, search, branch and date filters. */
    public function index(Request $request)
    {
        $tab = array_key_exists($request->tab, self::TABS) ? $request->tab : 'all';
        $base = Order::query()
            ->when($request->branch, fn ($q, $b) => $q->where('branch_id', $b))
            ->when($request->from, fn ($q, $d) => $q->where('created_at', '>=', now(OrderService::TZ)->parse($d)->startOfDay()->utc()))
            ->when($request->to, fn ($q, $d) => $q->where('created_at', '<', now(OrderService::TZ)->parse($d)->addDay()->startOfDay()->utc()))
            ->when(trim((string) $request->q), function ($q, $s) {
                $digits = preg_replace('/\D/', '', $s);
                $q->where(function ($w) use ($s, $digits) {
                    $w->where('customer_name', 'like', "%{$s}%")->orWhere('code', $s);
                    if ($digits !== '') {
                        $w->orWhere('id', (int) $digits)->orWhere('customer_phone', 'like', '%'.substr($digits, -7).'%');
                    }
                });
            });

        $counts = [];
        foreach (array_keys(self::TABS) as $t) {
            $counts[$t] = $this->tab(clone $base, $t)->count();
        }
        $list = $this->tab(clone $base, $tab);

        return view('admin.orders', [
            'orders' => $list->with('branch')->when($tab === 'scheduled', fn ($q) => $q->orderBy('scheduled_at'), fn ($q) => $q->latest('id'))
                ->paginate(25)->withQueryString(),
            'sum' => (float) $this->tab(clone $base, $tab)->where('status', '!=', 'cancelled')->sum('total'),
            'counts' => $counts,
            'tab' => $tab,
            'branches' => Branch::orderBy('sort')->get(),
        ]);
    }

    private function tab($q, string $tab)
    {
        if ($tab === 'scheduled') {
            return $q->whereNotNull('scheduled_at')->whereNotIn('status', ['completed', 'cancelled']);
        }
        return self::TABS[$tab] ? $q->whereIn('status', self::TABS[$tab]) : $q;
    }

    /** Live board like a delivery-app tablet: new, preparing, on the way, completed today. */
    public function board(Request $request)
    {
        $todayStart = now(OrderService::TZ)->startOfDay()->utc();
        $cols = [
            'new' => Order::with('items', 'branch')->where('status', 'new')->oldest('id')->get(),
            'preparing' => Order::with('items', 'branch')->whereIn('status', ['confirmed', 'preparing'])
                ->orderByRaw('scheduled_at IS NULL DESC')->orderBy('scheduled_at')->oldest('id')->get(),
            'out' => Order::with('items', 'branch')->where('status', 'out_for_delivery')->oldest('id')->get(),
            'done' => Order::with('items', 'branch')->where('status', 'completed')->where('updated_at', '>=', $todayStart)->latest('updated_at')->limit(12)->get(),
        ];
        return view($request->boolean('partial') ? 'admin._board' : 'admin.board', ['cols' => $cols]);
    }

    public function show(Order $order)
    {
        return view('admin.order', ['order' => $order->load('items', 'branch')]);
    }

    /** Kitchen ticket for an 80 mm receipt printer. */
    public function ticket(Order $order)
    {
        return view('admin.ticket', ['order' => $order->load('items', 'branch')]);
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->validate([
            'status' => 'required|in:new,confirmed,preparing,out_for_delivery,completed,cancelled',
            'payment_status' => 'nullable|in:unpaid,pending,paid,failed,refunded',
        ]));
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $order->status]);
        }
        return back()->with('ok', __('site.admin.saved'));
    }

    /** Pause or resume taking new orders on the site and the app. */
    public function pause(Request $request)
    {
        Setting::put('orders_paused', $request->boolean('paused'));
        return back()->with('ok', __($request->boolean('paused') ? 'admin.paused_on' : 'admin.paused_off'));
    }

    /** The next step for an order, as [status, label key], or null when it is finished. */
    public static function nextStep(Order $o): ?array
    {
        return match ($o->status) {
            'new' => ['confirmed', 'admin.next.new'],
            'confirmed' => ['preparing', 'admin.next.confirmed'],
            'preparing' => $o->fulfillment === 'delivery' ? ['out_for_delivery', 'admin.next.preparing_delivery'] : ['completed', 'admin.next.preparing_pickup'],
            'out_for_delivery' => ['completed', 'admin.next.out_for_delivery'],
            default => null,
        };
    }
}
