<div class="branches">
  @foreach($branches as $b)
  <div class="branch" id="{{ $b->slug }}">
    <h3>{{ $b->tr('name') }}</h3>
    <div class="addr"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $b->tr('address') }}@if($b->tr('hours')) · {{ $b->tr('hours') }}@endif</div>
    <div class="phones">@foreach($b->phones ?? [] as $p)<a href="tel:{{ str_replace(' ', '', $p) }}">{{ $p }}</a>@endforeach</div>
    <div class="acts">
      <a class="btn btn-wa" href="https://wa.me/{{ $b->whatsapp }}" target="_blank" rel="noopener">{{ __('site.whatsapp') }}</a>
      <a class="btn btn-ghost" href="tel:{{ str_replace(' ', '', $b->phones[0] ?? '') }}">{{ __('site.call') }}</a>
      <a class="btn btn-ghost" href="{{ $b->map_url ?: 'https://www.google.com/maps/search/?api=1&query='.urlencode('ربيع الإمارات للمندي '.$b->address_ar) }}" target="_blank" rel="noopener">{{ __('site.directions') }}</a>
    </div>
  </div>
  @endforeach
</div>
