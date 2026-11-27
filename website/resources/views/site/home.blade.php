@extends('layouts.site')
@section('title', __('site.nav.home'))
@section('content')
<section class="hero pattern">
  <div class="wrap">
    <div>
      <span class="eyebrow">{{ __('site.brand_sub') }} · {{ __('site.our_branches') }}: {{ $branches->map->tr('name')->join(' · ') }}</span>
      <h1>{{ __('site.brand') }}</h1>
      <p class="lead">{{ __('site.tagline') }}</p>
      <div class="hero-ctas">
        <a class="btn btn-primary" href="{{ route('menu') }}">{{ __('site.order_now') }}</a>
        <a class="btn btn-ghost" href="{{ route('menu') }}">{{ __('site.view_menu') }}</a>
      </div>
      <div class="hero-points">
        <span><i></i>{{ __('site.free_delivery') }}</span>
        <span><i></i>{{ __('site.family') }}</span>
        <span><i></i>{{ __('site.banquets') }}</span>
      </div>
    </div>
    <div class="arch-wrap">
      <div class="arch">
        <video src="{{ asset('images/brand/hero.mp4') }}" poster="{{ asset('images/brand/hero-poster.jpg') }}" autoplay muted loop playsinline preload="metadata"></video>
      </div>
      <img class="hero-logo" src="{{ asset('images/brand/logo.png') }}" alt="">
    </div>
  </div>
</section>

<div class="wrap">
  <div class="features">
    <div class="feature"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg></div><div><b>{{ __('site.free_delivery') }}</b><span>{{ __('site.free_delivery_sub') }}</span></div></div>
    <div class="feature"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-5a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v5"/><path d="M3 18h18"/><path d="M6 10V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v3"/></svg></div><div><b>{{ __('site.family') }}</b><span>{{ __('site.family_sub') }}</span></div></div>
    <div class="feature"><div class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="15" rx="9" ry="4"/><path d="M5 13c1-4 4-6 7-6s6 2 7 6"/><path d="M12 4v3"/></svg></div><div><b>{{ __('site.banquets') }}</b><span>{{ __('site.banquets_sub') }}</span></div></div>
  </div>
</div>

<section class="section">
  <div class="wrap">
    <div class="section-head"><h2>{{ __('site.featured') }}</h2><a class="link" href="{{ route('menu') }}">{{ __('site.view_menu') }} {{ app()->getLocale() === 'en' ? '→' : '←' }}</a></div>
    <div class="grid">
      @foreach($featured as $item) @include('site._dish', ['item' => $item]) @endforeach
    </div>
  </div>
</section>

<section class="section pattern" style="padding:56px 0">
  <div class="wrap">
    <div class="section-head"><h2>{{ __('site.categories') }}</h2></div>
    <div class="cats">
      @foreach($categories as $c)
        @php($img = $c->items()->whereNotNull('image')->value('image'))
        <a class="cat" href="{{ route('menu') }}#{{ $c->slug }}">
          @if($img)<img src="{{ asset($img) }}" alt="" loading="lazy">@endif
          {{ $c->tr('name') }}
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap about">
    <div>
      <span class="eyebrow">{{ __('site.brand') }}</span>
      <h2 style="font-size:clamp(1.7rem,3.4vw,2.4rem);margin:8px 0 14px">{{ __('site.about_title') }}</h2>
      <p>{{ __('site.about_text') }}</p>
      <a class="btn btn-primary" style="margin-top:12px" href="{{ route('branches') }}">{{ __('site.our_branches') }}</a>
    </div>
    <div class="mosaic">
      <img src="{{ asset('images/photos/hero-lamb-reveal.webp') }}" alt="" loading="lazy">
      <img src="{{ asset('images/brand/majlis.webp') }}" alt="" loading="lazy">
      <img src="{{ asset('images/brand/dining.webp') }}" alt="" loading="lazy">
    </div>
  </div>
</section>


<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="gallery">
      @foreach(['hero-chicken', 'hero-whole-lamb', 'hero-fattoush', 'chicken-stuffed', 'lamb-mukalkal', 'fish-suprem'] as $g)
        <img src="{{ asset('images/photos/'.$g.'.webp') }}" alt="" loading="lazy">
      @endforeach
    </div>
  </div>
</section>
<section class="section pattern" style="padding:56px 0">
  <div class="wrap">
    <div class="section-head"><h2>{{ __('site.our_branches') }}</h2></div>
    @include('site._branches', ['branches' => $branches])
  </div>
</section>

@include('site._items_json', ['items' => $featured])
@endsection
