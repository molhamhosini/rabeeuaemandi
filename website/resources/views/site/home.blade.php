@extends('layouts.site')
@section('title', __('site.nav.home'))
@section('content')
@php($arrow = app()->getLocale() === 'en' ? '→' : '←')
<section class="hero">
  <div class="hero-glow"></div>
  <div class="wrap">
    <div class="hero-copy">
      <img class="hero-wm" src="{{ asset('images/brand/logo-wordmark-gold.png') }}" alt="{{ __('site.brand') }}">
      <span class="eyebrow light">{{ __('site.hero_eyebrow') }}</span>
      <h1>{{ __('site.tagline') }}</h1>
      <div class="hero-ctas">
        <a class="btn btn-gold btn-lg" href="{{ route('menu') }}">{{ __('site.order_now') }}</a>
        <a class="btn btn-outline-light btn-lg" href="{{ route('branches') }}">{{ __('site.our_branches') }}</a>
      </div>
      <ul class="hero-chips">
        <li><svg viewBox="0 0 24 24"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/></svg>{{ __('site.free_delivery') }}</li>
        <li><svg viewBox="0 0 24 24"><path d="M3 18v-5a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v5"/><path d="M3 18h18"/><path d="M6 10V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v3"/></svg>{{ __('site.family') }}</li>
        <li><svg viewBox="0 0 24 24"><ellipse cx="12" cy="15" rx="9" ry="4"/><path d="M5 13c1-4 4-6 7-6s6 2 7 6"/><path d="M12 4v3"/></svg>{{ __('site.banquets') }}</li>
      </ul>
    </div>
    <div class="arch-wrap">
      <div class="arch-ring"></div>
      <div class="arch">
        <video src="{{ asset('images/brand/hero.mp4') }}" poster="{{ asset('images/brand/hero-poster.jpg') }}" autoplay muted loop playsinline preload="metadata"></video>
      </div>
      <img class="hero-badge" src="{{ asset('images/brand/logo.png') }}" alt="">
    </div>
  </div>
  <div class="hero-edge"></div>
</section>

<section class="section">
  <div class="wrap">
    <header class="title">
      <span class="eyebrow">{{ __('site.featured_sub') }}</span>
      <h2>{{ __('site.featured') }}</h2>
      <i class="orn"></i>
    </header>
    <div class="grid grid-3">
      @foreach($featured as $item) @include('site._dish', ['item' => $item]) @endforeach
    </div>
    <div class="center"><a class="btn btn-primary" href="{{ route('menu') }}">{{ __('site.view_menu') }} {{ $arrow }}</a></div>
  </div>
</section>

<section class="section tint">
  <div class="wrap">
    <header class="title">
      <span class="eyebrow">{{ __('site.categories_sub') }}</span>
      <h2>{{ __('site.categories') }}</h2>
      <i class="orn"></i>
    </header>
    <div class="cats">
      @foreach($categories as $c)
        @php($img = $c->items()->whereNotNull('image')->where('image', 'not like', '%emblem%')->value('image') ?? $c->items()->whereNotNull('image')->value('image'))
        <a class="cat" href="{{ route('menu') }}#{{ $c->slug }}">
          <span class="cat-ph">@if($img)<img src="{{ asset($img) }}" alt="" loading="lazy">@endif</span>
          <b>{{ $c->tr('name') }}</b>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap about">
    <div class="mosaic">
      <img src="{{ asset('images/photos/hero-lamb-reveal.webp') }}" alt="" loading="lazy">
      <img src="{{ asset('images/brand/majlis.webp') }}" alt="" loading="lazy">
      <img src="{{ asset('images/brand/dining.webp') }}" alt="" loading="lazy">
    </div>
    <div class="about-copy">
      <span class="eyebrow">{{ __('site.brand') }}</span>
      <h2>{{ __('site.about_title') }}</h2>
      <i class="orn start"></i>
      <p>{{ __('site.about_text') }}</p>
      <div class="about-feats">
        <div><b>{{ __('site.free_delivery') }}</b><span>{{ __('site.free_delivery_sub') }}</span></div>
        <div><b>{{ __('site.family') }}</b><span>{{ __('site.family_sub') }}</span></div>
        <div><b>{{ __('site.banquets') }}</b><span>{{ __('site.banquets_sub') }}</span></div>
      </div>
    </div>
  </div>
</section>

<section class="strip">
  <div class="gallery">
    @foreach(['hero-chicken', 'hero-whole-lamb', 'hero-fattoush', 'chicken-stuffed', 'lamb-mukalkal', 'fish-suprem'] as $g)
      <img src="{{ asset('images/photos/'.$g.'.webp') }}" alt="" loading="lazy">
    @endforeach
  </div>
</section>

<section class="section tint">
  <div class="wrap">
    <header class="title">
      <span class="eyebrow">{{ __('site.branches_sub') }}</span>
      <h2>{{ __('site.our_branches') }}</h2>
      <i class="orn"></i>
    </header>
    @include('site._branches', ['branches' => $branches])
  </div>
</section>

@include('site._items_json', ['items' => $featured])
@endsection
