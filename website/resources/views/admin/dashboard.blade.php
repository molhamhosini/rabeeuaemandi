@extends('admin.layout')
@section('title', __('admin.dashboard'))
@section('heading', __('admin.hello').' 👋')
@php($m = fn ($v) => \App\Services\OrderService::money($v))
@php($i = fn ($p) => '<svg viewBox="0 0 24 24">'.$p.'</svg>')
@section('content')
<div class="grid g4">
  <div class="card kpi"><span class="ico">{!! $i('<path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/>') !!}</span>
    <span class="lbl">{{ __('admin.orders_today') }}</span><b class="val">{{ $stats['count'] }}</b><span class="sub">{{ __('admin.vs_yesterday', ['v' => $stats['y_count']]) }}</span></div>
  <div class="card kpi"><span class="ico">{!! $i('<path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/>') !!}</span>
    <span class="lbl">{{ __('admin.sales_today') }}</span><b class="val">{{ $m($stats['sales']) }} <small style="font-size:.9rem">{{ __('site.aed') }}</small></b><span class="sub">{{ __('admin.vs_yesterday', ['v' => $m($stats['y_sales'])]) }}</span></div>
  <div class="card kpi"><span class="ico">{!! $i('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>') !!}</span>
    <span class="lbl">{{ __('admin.in_progress') }}</span><b class="val">{{ $stats['active'] }}</b><span class="sub">{{ __('admin.avg_order') }}: {{ $m($stats['avg']) }}</span></div>
  <a href="{{ route('admin.board') }}" @class(['card kpi', 'hot' => $stats['new'] > 0])><span class="ico">{!! $i('<path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>') !!}</span>
    <span class="lbl">{{ __('admin.waiting') }}</span><b class="val">{{ $stats['new'] }}</b><span class="sub">{{ __('admin.board') }} ←</span></a>
</div>

<div class="grid g2" style="margin-top:16px">
  <div class="card">
    <h3>{{ __('admin.sales_14') }}</h3>
    @php($max = max(1, max(array_column($chart, 'total'))))
    <div class="bars">
      @foreach($chart as $d)
        <div @class(['b', 'today' => $loop->last]) title="{{ $d['day']->format('d/m') }}: {{ $m($d['total']) }} {{ __('site.aed') }} · {{ $d['count'] }}">
          <em>{{ $d['total'] > 0 ? $m($d['total']) : '' }}</em>
          <i style="height:{{ round($d['total'] / $max * 100) }}%"></i>
          <small>{{ $d['day']->format('d/m') }}</small>
        </div>
      @endforeach
    </div>
  </div>
  <div class="card">
    <h3>{{ __('admin.latest_new') }} <a href="{{ route('admin.board') }}">{{ __('admin.see_all') }}</a></h3>
    <div class="rows">
      @forelse($fresh as $o)
        <a class="r" href="{{ route('admin.orders.show', $o) }}" style="color:inherit">
          <span class="t"><b dir="auto">#{{ $o->number }} · {{ $o->customer_name }}</b><small>{{ $o->branch->tr('name') }} · {{ $o->created_at->diffForHumans() }}</small></span>
          <b>{{ $m($o->total) }}</b>
        </a>
      @empty
        <p class="empty">{{ __('admin.no_new') }}</p>
      @endforelse
    </div>
  </div>
</div>

<div class="grid g2e" style="margin-top:16px">
  <div class="card">
    <h3>{{ __('admin.upcoming') }} <a href="{{ route('admin.orders', ['tab' => 'scheduled']) }}">{{ __('admin.see_all') }}</a></h3>
    <div class="rows">
      @forelse($upcoming as $o)
        <a class="r" href="{{ route('admin.orders.show', $o) }}" style="color:inherit">
          <span class="t"><b>#{{ $o->number }} · {{ $o->customer_name }}</b><small>{{ $o->branch->tr('name') }} · <span class="st st-{{ $o->status }}">{{ __('site.order.statuses.'.$o->status) }}</span></small></span>
          <span class="when">{{ $o->whenText() }}</span>
        </a>
      @empty
        <p class="empty">{{ __('admin.no_upcoming') }}</p>
      @endforelse
    </div>
  </div>
  <div class="card">
    <h3>{{ __('admin.top_dishes') }}</h3>
    <div class="rows">
      @forelse($top->values() as $k => $r)
        <div class="r"><span class="rank">{{ $k + 1 }}</span>
          @if($r['item']->image)<img src="{{ $r['item']->imageUrl() }}" alt="">@endif
          <span class="t"><b>{{ $r['item']->tr('name') }}</b><small>{{ $r['qty'] }} {{ __('admin.pcs') }}</small></span>
          <b>{{ $m($r['sales']) }}</b></div>
      @empty
        <p class="empty">—</p>
      @endforelse
    </div>
    <h3 style="margin-top:18px">{{ __('admin.by_branch') }}</h3>
    <div class="rows">
      @foreach($branches as $b)
        <div class="r"><span class="t"><b>{{ $b['name'] }}</b><small>{{ __('admin.results', ['n' => $b['count']]) }}</small></span><b>{{ $m($b['sales']) }} {{ __('site.aed') }}</b></div>
      @endforeach
    </div>
  </div>
</div>
@endsection
