@extends('layouts.site')
@section('title', __('site.nav.menu'))
@section('content')
<section class="menu-hero pattern">
  <div class="wrap"><h1>{{ __('site.nav.menu') }}</h1><p style="color:var(--muted);margin:6px 0 0">{{ __('site.prices_incl_vat') }}</p></div>
</section>
<nav class="tabs"><div class="wrap">
  @foreach($categories as $c)<a href="#{{ $c->slug }}" data-tab="{{ $c->slug }}">{{ $c->tr('name') }}</a>@endforeach
</div></nav>
<div class="wrap" style="padding-bottom:40px">
  @foreach($categories as $c)
  <section class="menu-sec" id="{{ $c->slug }}" data-sec>
    <h2>{{ $c->tr('name') }}</h2>
    @if($c->tr('note'))<p class="note">{{ $c->tr('note') }}</p>@else<p class="note"></p>@endif
    <div class="grid">@foreach($c->activeItems as $item) @include('site._dish', ['item' => $item]) @endforeach</div>
  </section>
  @endforeach
  <p class="vat">{{ __('site.prices_incl_vat') }}</p>
</div>
@include('site._items_json', ['items' => $categories->flatMap->activeItems])
@push('scripts')
<script>
(() => {
  const tabs = [...document.querySelectorAll('[data-tab]')];
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    tabs.forEach(t => t.classList.toggle('on', t.dataset.tab === e.target.id));
    const on = tabs.find(t => t.dataset.tab === e.target.id); on && on.scrollIntoView({block:'nearest', inline:'center', behavior:'smooth'});
  }), {rootMargin: '-45% 0px -50% 0px'});
  document.querySelectorAll('[data-sec]').forEach(s => io.observe(s));
})();
</script>
@endpush
@endsection
