@extends('admin.layout')
@section('content')
<h1>{{ __('site.order.title') }} <span dir="ltr">#{{ $order->code }}</span></h1>
<div class="two">
  <div class="card">
    <h3>{{ __('site.admin.customer') }}</h3>
    <p><b>{{ $order->customer_name }}</b><br><a dir="ltr" href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a>
    · <a class="link" href="https://wa.me/{{ preg_replace('/^0/', '971', preg_replace('/\D/', '', $order->customer_phone)) }}" target="_blank">WhatsApp</a></p>
    <p>{{ $order->fulfillment === 'delivery' ? __('site.checkout.delivery').': '.$order->address : __('site.checkout.pickup') }}</p>
    @if($order->notes)<p>{{ __('site.checkout.notes') }}: {{ $order->notes }}</p>@endif
    <p>{{ $order->branch->tr('name') }} · {{ $order->created_at->timezone('Asia/Dubai')->format('d/m/Y H:i') }}</p>
  </div>
  <form class="card" method="post" action="{{ route('admin.orders.update', $order) }}">
    @csrf @method('patch')
    <h3>{{ __('site.order.status') }}</h3>
    <div class="field"><select name="status">@foreach(__('site.order.statuses') as $k => $v)<option value="{{ $k }}" @selected($order->status === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="field"><label>{{ __('site.order.payment') }} ({{ $order->payment_method }})</label><select name="payment_status">@foreach(__('site.order.payments') as $k => $v)<option value="{{ $k }}" @selected($order->payment_status === $k)>{{ $v }}</option>@endforeach</select></div>
    <button class="btn btn-primary">{{ __('site.admin.save') }}</button>
  </form>
</div>
<div class="card" style="margin-top:14px">
  @foreach($order->items as $i)<div class="sum" style="color:var(--ink)"><span>{{ $i->quantity }} × {{ app()->getLocale() === 'en' ? $i->name_en : $i->name_ar }}</span><span>{{ \App\Services\OrderService::money($i->line_total) }}</span></div>@endforeach
  <div class="sum total"><span>{{ __('site.cart.total') }}</span><span>{{ \App\Services\OrderService::money($order->total) }} {{ __('site.aed') }}</span></div>
</div>
@endsection
