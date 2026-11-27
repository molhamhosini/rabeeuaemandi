@extends('admin.layout')
@section('content')
<h1>{{ __('site.admin.orders') }}</h1>
<div class="stats">
  <div class="stat"><b>{{ $stats['today'] }}</b><span>{{ __('site.admin.today') }}</span></div>
  <div class="stat"><b>{{ \App\Services\OrderService::money($stats['today_total']) }} {{ __('site.aed') }}</b><span>{{ __('site.admin.today_total') }}</span></div>
  <div class="stat"><b>{{ $stats['new'] }}</b><span>{{ __('site.admin.new_orders') }}</span></div>
</div>
<div class="filters">
  <a href="{{ route('admin.orders') }}" @class(['on' => ! request('branch')])>{{ __('site.admin.all') }}</a>
  @foreach($branches as $b)<a href="?branch={{ $b->id }}" @class(['on' => request('branch') == $b->id])>{{ $b->tr('name') }}</a>@endforeach
</div>
<div class="tbl"><table>
  <tr><th>#</th><th>{{ __('site.admin.time') }}</th><th>{{ __('site.admin.customer') }}</th><th>{{ __('site.checkout.branch') }}</th><th>{{ __('site.cart.total') }}</th><th>{{ __('site.order.payment') }}</th><th>{{ __('site.order.status') }}</th><th>{{ __('site.admin.source') }}</th></tr>
  @forelse($orders as $o)
  <tr onclick="location='{{ route('admin.orders.show', $o) }}'" style="cursor:pointer">
    <td dir="ltr"><b>{{ $o->code }}</b></td>
    <td>{{ $o->created_at->timezone('Asia/Dubai')->format('d/m H:i') }}</td>
    <td>{{ $o->customer_name }}<br><small dir="ltr">{{ $o->customer_phone }}</small></td>
    <td>{{ $o->branch->tr('name') }}</td>
    <td><b>{{ \App\Services\OrderService::money($o->total) }}</b></td>
    <td><span class="st st-{{ $o->payment_status }}">{{ $o->payment_method === 'card' ? '💳' : 'WhatsApp' }} · {{ __('site.order.payments.'.$o->payment_status) }}</span></td>
    <td><span class="st st-{{ $o->status }}">{{ __('site.order.statuses.'.$o->status) }}</span></td>
    <td>{{ $o->source === 'app' ? 'App' : 'Web' }}</td>
  </tr>
  @empty
  <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px">{{ __('site.admin.no_orders') }}</td></tr>
  @endforelse
</table></div>
<div class="filters" style="margin-top:14px">
  @if($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}">‹</a>@endif
  @if($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">›</a>@endif
</div>
@endsection
