@php($m = fn ($v) => \App\Services\OrderService::money($v))
@php($en = app()->getLocale() === 'en')
@php($heads = ['new' => __('admin.col_new'), 'preparing' => __('admin.col_preparing'), 'out' => __('admin.col_out'), 'done' => __('admin.col_done')])
<div class="board">
  @foreach($cols as $key => $orders)
    <section class="col c-{{ $key }}">
      <div class="col-h"><span>{{ $heads[$key] }}</span><span class="n">{{ $orders->count() }}</span></div>
      @forelse($orders as $o)
        @php($next = \App\Http\Controllers\Admin\OrderController::nextStep($o))
        <article class="oc">
          <div class="oc-h">
            <a href="{{ route('admin.orders.show', $o) }}" dir="ltr">#{{ $o->number }}</a>
            <span class="age" title="{{ $o->created_at->timezone(\App\Services\OrderService::TZ)->format('d/m H:i') }}">{{ $o->created_at->diffForHumans(null, true) }}</span>
          </div>
          <div class="meta">
            <span>{{ $o->customer_name }}</span>
            <span>{{ __('admin.'.($o->fulfillment === 'delivery' ? 'delivery' : 'pickup')) }}</span>
            <span>{{ $o->branch->tr('name') }}</span>
            @if($o->status === 'confirmed')<span class="st st-confirmed">{{ __('site.order.statuses.confirmed') }}</span>@endif
          </div>
          @if($o->scheduled_at)<span class="when">⏰ {{ $o->whenText() }}</span>@endif
          <ul>
            @foreach($o->items->take(5) as $i)
              <li><b>{{ $i->quantity }}×</b> {{ $en ? $i->name_en : $i->name_ar }}@if($d = $en ? $i->details_en : $i->details_ar)<small>{{ $d }}</small>@endif</li>
            @endforeach
            @if($o->items->count() > 5)<li>+{{ $o->items->count() - 5 }}…</li>@endif
          </ul>
          @if($o->notes)<p style="margin:4px 0;color:#8A4B00;font-size:.85rem;font-weight:700">📝 {{ $o->notes }}</p>@endif
          <div class="foot">
            <b>{{ $m($o->total) }} <small>{{ __('site.aed') }}</small></b>
            <span class="acts">
              <button type="button" class="btn-s ghost" data-print="{{ route('admin.orders.ticket', $o) }}" title="{{ __('admin.print') }}"><svg viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 14h12v7H6z"/></svg></button>
              @if($o->status === 'new')
                <button type="button" class="btn-s no" data-step="{{ route('admin.orders.update', $o) }}" data-status="cancelled">{{ __('admin.reject') }}</button>
              @endif
              @if($next)
                <button type="button" class="btn-s {{ $o->status === 'new' ? 'ok' : '' }}" data-step="{{ route('admin.orders.update', $o) }}" data-status="{{ $next[0] }}" data-ticket="{{ route('admin.orders.ticket', $o) }}">{{ __($next[1]) }}</button>
              @endif
            </span>
          </div>
        </article>
      @empty
        <p class="empty">{{ __('admin.empty_col') }}</p>
      @endforelse
    </section>
  @endforeach
</div>
