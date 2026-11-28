@extends('admin.layout')
@section('title', '#'.$order->number)
@section('heading')<span dir="ltr">#{{ $order->number }}</span> <span class="st st-{{ $order->status }}" style="font-size:.85rem;vertical-align:middle">{{ __('site.order.statuses.'.$order->status) }}</span>@endsection
@php($m = fn ($v) => \App\Services\OrderService::money($v))
@php($en = app()->getLocale() === 'en')
@php($next = \App\Http\Controllers\Admin\OrderController::nextStep($order))
@php($wa = preg_replace('/^0/', '971', preg_replace('/\D/', '', $order->customer_phone)))
@section('content')
<p style="margin:0 0 12px"><a class="link" href="{{ route('admin.orders') }}">← {{ __('admin.back') }}</a></p>
<div class="op">
  <div>
    <div class="card" style="margin-bottom:16px">
      <h3>{{ __('admin.status') }}</h3>
      <div class="acts-row" style="margin-bottom:12px">
        @if($next)<button type="button" class="btn-s ok lg" data-step="{{ route('admin.orders.update', $order) }}" data-status="{{ $next[0] }}" data-ticket="{{ route('admin.orders.ticket', $order) }}">{{ __($next[1]) }}</button>@endif
        <button type="button" class="btn-s gold lg" data-print="{{ route('admin.orders.ticket', $order) }}"><svg viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg>{{ __('admin.print') }}</button>
        @unless(in_array($order->status, ['cancelled', 'completed']))
          <button type="button" class="btn-s no lg" data-step="{{ route('admin.orders.update', $order) }}" data-status="cancelled" data-confirm="{{ __('admin.cancel_confirm') }}">{{ __('admin.cancel') }}</button>
        @endunless
      </div>
      <div class="steps">
        @foreach(__('site.order.statuses') as $k => $v)
          <button type="button" @class(['cur' => $order->status === $k]) data-step="{{ route('admin.orders.update', $order) }}" data-status="{{ $k }}" @if($k === 'cancelled') data-confirm="{{ __('admin.cancel_confirm') }}" @endif>{{ $v }}</button>
        @endforeach
      </div>
    </div>
    <div class="card">
      <h3>{{ __('admin.items') }}</h3>
      @foreach($order->items as $i)
        <div class="line"><span><b>{{ $i->quantity }} × {{ $en ? $i->name_en : $i->name_ar }}</b>@if($d = $en ? $i->details_en : $i->details_ar)<small>{{ $d }}</small>@endif</span><b>{{ $m($i->line_total) }}</b></div>
      @endforeach
      <div class="line"><span>{{ __('admin.receipt.subtotal') }}</span><span>{{ $m($order->subtotal) }}</span></div>
      @if((float) $order->delivery_fee > 0)<div class="line"><span>{{ __('admin.receipt.delivery_fee') }}</span><span>{{ $m($order->delivery_fee) }}</span></div>@endif
      <div class="line" style="font-size:1.15rem"><b>{{ __('site.cart.total') }}</b><b>{{ $m($order->total) }} {{ __('site.aed') }}</b></div>
    </div>
  </div>
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="big-when">⏰ {{ __('admin.when') }}: {{ $order->whenText() }}</div>
      <dl class="kv">
        <dt>{{ __('site.admin.customer') }}</dt><dd>{{ $order->customer_name }}</dd>
        <dt>{{ __('admin.receipt.phone') }}</dt><dd dir="ltr" style="text-align:start">{{ $order->customer_phone }}</dd>
        <dt>{{ __('admin.receipt.branch') }}</dt><dd>{{ $order->branch->tr('name') }}</dd>
        <dt>{{ __('admin.placed') }}</dt><dd>{{ $order->created_at->timezone(\App\Services\OrderService::TZ)->format('d/m/Y h:i A') }}</dd>
        <dt>{{ $order->fulfillment === 'delivery' ? __('admin.delivery') : __('admin.pickup') }}</dt><dd>{{ $order->fulfillment === 'delivery' ? $order->address : '—' }}</dd>
        @if($order->notes)<dt>{{ __('admin.notes') }}</dt><dd style="color:#8A4B00">{{ $order->notes }}</dd>@endif
        <dt>{{ __('site.admin.source') }}</dt><dd>{{ $order->source === 'app' ? 'App' : 'Web' }}</dd>
      </dl>
      <div class="acts-row" style="margin-top:14px">
        <a class="btn-s ghost" href="tel:{{ $order->customer_phone }}">📞 {{ __('admin.call') }}</a>
        <a class="btn-s ghost" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">💬 {{ __('admin.whatsapp') }}</a>
        @if($order->fulfillment === 'delivery' && $order->address)<a class="btn-s ghost" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->address) }}" target="_blank" rel="noopener">📍 {{ __('admin.map') }}</a>@endif
      </div>
    </div>
    <form class="card" method="post" action="{{ route('admin.orders.update', $order) }}">
      @csrf @method('patch')
      <input type="hidden" name="status" value="{{ $order->status }}">
      <h3>{{ __('admin.payment') }}: {{ __('admin.receipt.pay_'.$order->payment_method) }}</h3>
      <div class="field"><select name="payment_status">@foreach(__('site.order.payments') as $k => $v)<option value="{{ $k }}" @selected($order->payment_status === $k)>{{ $v }}</option>@endforeach</select></div>
      <button class="btn-s">{{ __('site.admin.save') }}</button>
    </form>
  </div>
</div>
@endsection
