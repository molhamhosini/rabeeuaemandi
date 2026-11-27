@extends('layouts.site')
@section('title', __('site.order.title').' '.$order->code)
@section('content')
@php($en = app()->getLocale() === 'en')
<div class="wrap" style="max-width:640px;padding-top:40px;padding-bottom:60px">
  <div class="card" style="text-align:center">
    <div class="ok-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg></div>
    <h1 style="font-size:1.8rem">{{ __('site.order.thanks') }}</h1>
    <p style="color:var(--muted);margin:6px 0 14px">{{ __('site.order.title') }} <b dir="ltr">#{{ $order->code }}</b> · {{ $en ? $order->branch->name_en : $order->branch->name_ar }}</p>
    <p>
      <span class="pill">{{ __('site.order.status') }}: {{ __('site.order.statuses.'.$order->status) }}</span>
      <span class="pill">{{ __('site.order.payment') }}: {{ __('site.order.payments.'.$order->payment_status) }}</span>
    </p>
    @if(request('paid') || $order->payment_status === 'paid')
      <p>{{ __('site.order.paid') }}</p>
    @elseif(request('cancelled'))
      <p style="color:var(--red)">{{ __('site.order.cancelled') }}</p>
      <a class="btn btn-wa" href="{{ $whatsapp }}" target="_blank" rel="noopener">{{ __('site.checkout.submit_whatsapp') }}</a>
    @elseif($order->payment_method === 'whatsapp')
      <p style="color:var(--muted)">{{ __('site.order.whatsapp_hint') }}</p>
      <a class="btn btn-wa" href="{{ $whatsapp }}" target="_blank" rel="noopener">{{ __('site.checkout.submit_whatsapp') }}</a>
    @endif
  </div>
  <div class="card">
    @foreach($order->items as $i)
      <div class="sum" style="color:var(--ink)"><span>{{ $i->quantity }} × {{ $en ? $i->name_en : $i->name_ar }}</span><span>{{ \App\Services\OrderService::money($i->line_total) }}</span></div>
    @endforeach
    <div class="sum total"><span>{{ __('site.cart.total') }}</span><span>{{ \App\Services\OrderService::money($order->total) }} {{ __('site.aed') }}</span></div>
  </div>
</div>
@if(request('paid'))<script>try{localStorage.removeItem('cart')}catch(e){}</script>@endif
@if(request('wa') && $order->payment_method === 'whatsapp')<script>setTimeout(() => { location.href = @json($whatsapp); }, 900);</script>@endif
@endsection
