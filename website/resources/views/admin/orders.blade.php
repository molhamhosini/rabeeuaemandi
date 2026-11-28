@extends('admin.layout')
@section('title', __('admin.history'))
@section('heading', __('admin.history'))
@php($m = fn ($v) => \App\Services\OrderService::money($v))
@section('content')
<nav class="tabs">
  @foreach(array_keys(\App\Http\Controllers\Admin\OrderController::TABS) as $t)
    <a href="{{ request()->fullUrlWithQuery(['tab' => $t, 'page' => null]) }}" @class(['on' => $tab === $t])>{{ __('admin.tab_'.$t) }}<i>{{ $counts[$t] }}</i></a>
  @endforeach
</nav>
<form class="fbar" method="get">
  <input type="hidden" name="tab" value="{{ $tab }}">
  <input class="q" type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('admin.search_ph') }}">
  <select name="branch"><option value="">{{ __('admin.all_branches') }}</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->tr('name') }}</option>@endforeach</select>
  <label>{{ __('admin.from') }}<input type="date" name="from" value="{{ request('from') }}"></label>
  <label>{{ __('admin.to') }}<input type="date" name="to" value="{{ request('to') }}"></label>
  <button class="btn-s">{{ __('admin.filter') }}</button>
  @if(request()->hasAny(['q', 'branch', 'from', 'to']))<a class="btn-s ghost" href="{{ route('admin.orders', ['tab' => $tab]) }}">{{ __('admin.clear') }}</a>@endif
</form>
<div class="sumline"><span>{{ __('admin.results', ['n' => $orders->total()]) }}</span><span>{{ __('admin.total_of', ['v' => $m($sum).' '.__('site.aed')]) }}</span></div>
<table class="t">
  <thead><tr><th>#</th><th>{{ __('site.admin.customer') }}</th><th>{{ __('admin.receipt.branch') }}</th><th>{{ __('admin.when') }}</th><th>{{ __('admin.status') }}</th><th>{{ __('admin.payment') }}</th><th>{{ __('site.cart.total') }}</th><th></th></tr></thead>
  <tbody>
  @forelse($orders as $o)
    @php($next = \App\Http\Controllers\Admin\OrderController::nextStep($o))
    <tr data-href="{{ route('admin.orders.show', $o) }}">
      <td><b dir="ltr">#{{ $o->number }}</b><br><small>{{ $o->created_at->timezone(\App\Services\OrderService::TZ)->format('d/m H:i') }}</small></td>
      <td><b>{{ $o->customer_name }}</b><br><small dir="ltr">{{ $o->customer_phone }}</small></td>
      <td>{{ $o->branch->tr('name') }}<br><small>{{ __('admin.'.($o->fulfillment === 'delivery' ? 'delivery' : 'pickup')) }}</small></td>
      <td>@if($o->scheduled_at)<span class="when">{{ $o->whenText() }}</span>@else<small>{{ __('admin.asap') }}</small>@endif</td>
      <td><span class="st st-{{ $o->status }}">{{ __('site.order.statuses.'.$o->status) }}</span></td>
      <td><small>{{ __('admin.receipt.pay_'.$o->payment_method) }}</small><br><span class="st st-{{ $o->payment_status }}">{{ __('site.order.payments.'.$o->payment_status) }}</span></td>
      <td><b>{{ $m($o->total) }}</b></td>
      <td style="white-space:nowrap">
        <button type="button" class="btn-s ghost" data-print="{{ route('admin.orders.ticket', $o) }}" title="{{ __('admin.print') }}"><svg viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg></button>
        @if($next)<button type="button" class="btn-s" data-step="{{ route('admin.orders.update', $o) }}" data-status="{{ $next[0] }}" data-ticket="{{ route('admin.orders.ticket', $o) }}">{{ __($next[1]) }}</button>@endif
      </td>
    </tr>
  @empty
    <tr><td colspan="8" class="empty">—</td></tr>
  @endforelse
  </tbody>
</table>
@if($orders->hasPages())
<div class="pager">
  @if($orders->onFirstPage())<span>‹</span>@else<a href="{{ $orders->previousPageUrl() }}">‹</a>@endif
  <span style="opacity:1">{{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>
  @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">›</a>@else<span>›</span>@endif
</div>
@endif
@endsection
