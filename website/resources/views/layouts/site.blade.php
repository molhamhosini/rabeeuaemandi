@php($rtl = app()->getLocale() !== 'en')
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@yield('title', __('site.brand')) | {{ __('site.brand') }}</title>
<meta name="description" content="{{ __('site.tagline') }}">
<meta name="theme-color" content="#5B3E22">
<link rel="icon" href="{{ asset('images/brand/icon-192.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/brand/icon-192.png') }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta property="og:title" content="{{ __('site.brand') }}">
<meta property="og:description" content="{{ __('site.tagline') }}">
<meta property="og:image" content="{{ asset('images/photos/hero-chicken.webp') }}">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Reem+Kufi:wght@500;600;700&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="{{ $rtl ? 'ar' : 'en' }}">
<header class="hdr">
  <div class="wrap">
    <a class="logo wm" href="{{ route('home') }}" aria-label="{{ __('site.brand') }}">
      <img src="{{ asset('images/brand/logo-wordmark.png') }}" alt="{{ __('site.brand') }}">
    </a>
    <nav class="nav">
      <a href="{{ route('home') }}" @class(['on' => request()->routeIs('home')])>{{ __('site.nav.home') }}</a>
      <a href="{{ route('menu') }}" @class(['on' => request()->routeIs('menu')])>{{ __('site.nav.menu') }}</a>
      <a href="{{ route('branches') }}" @class(['on' => request()->routeIs('branches')])>{{ __('site.nav.branches') }}</a>
    </nav>
    <div class="hdr-actions">
      <a class="lang" href="?lang={{ $rtl ? 'en' : 'ar' }}">{{ $rtl ? 'EN' : 'ع' }}</a>
      <button class="cart-btn" data-open-cart aria-label="{{ __('site.nav.cart') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
        <span class="lbl">{{ __('site.nav.cart') }}</span><span class="badge" data-cart-count>0</span>
      </button>
    </div>
  </div>
</header>

<main>@yield('content')</main>

<footer class="ftr">
  <div class="wrap">
    <div>
      <img class="ftr-wm" src="{{ asset('images/brand/logo-wordmark-light.png') }}" alt="{{ __('site.brand') }}">
      <p style="opacity:.85;margin-top:14px">{{ __('site.tagline') }}</p>
    </div>
    <div>
      <h4>{{ __('site.our_branches') }}</h4>
      @foreach(\App\Models\Branch::active()->get() as $b)
        <a href="{{ route('branches') }}#{{ $b->slug }}">{{ $b->tr('name') }}</a>
        <a href="tel:{{ str_replace(' ', '', $b->phones[0] ?? '') }}" dir="ltr">{{ $b->phones[0] ?? '' }}</a>
      @endforeach
    </div>
    <div>
      <h4>{{ __('site.footer.follow') }}</h4>
      <a href="https://instagram.com/rabeeemaratmandi" target="_blank" rel="noopener">Instagram</a>
      <a href="mailto:info@rabeeuaemandi.com">info@rabeeuaemandi.com</a>
      <a href="{{ route('menu') }}">{{ __('site.nav.menu') }}</a>
    </div>
    <div class="copy"><span>© {{ date('Y') }} {{ __('site.brand') }}. {{ __('site.footer.rights') }}</span><span>{{ __('site.prices_incl_vat') }}</span></div>
  </div>
</footer>

<button class="cartbar" data-open-cart><span><span data-cart-count>0</span> {{ __('site.cart.items') }}</span><span>{{ __('site.cart.checkout') }} · <span data-cart-total>0</span> {{ __('site.aed') }}</span></button>

<div class="scrim" data-scrim></div>
<aside class="drawer" data-drawer aria-label="{{ __('site.cart.title') }}">
  <div class="drawer-h"><h3>{{ __('site.cart.title') }}</h3><button class="x" data-close>&times;</button></div>
  <div class="drawer-b" data-cart-lines></div>
  <div class="drawer-f" data-cart-foot>
    <div class="sum"><span>{{ __('site.cart.subtotal') }}</span><span><span data-cart-total>0</span> {{ __('site.aed') }}</span></div>
    <div class="sum"><span>{{ __('site.cart.delivery') }}</span><span>{{ __('site.cart.free') }}</span></div>
    <a class="btn btn-primary btn-block" style="margin-top:12px" href="{{ route('checkout') }}">{{ __('site.cart.checkout') }}</a>
  </div>
</aside>
<div class="sheet" data-sheet></div>
<div class="toast" data-toast></div>

<script>
window.SITE = {
  locale: @json(app()->getLocale()),
  aed: @json(__('site.aed')),
  t: { empty: @json(__('site.cart.empty')), emptySub: @json(__('site.cart.empty_sub')), added: @json(__('site.added')), add: @json(__('site.add')), choose: @json(__('site.choose')), sending: @json(__('site.checkout.sending')) },
};
</script>
<script src="{{ asset('js/cart.js') }}?v=1"></script>
@stack('scripts')
</body>
</html>
