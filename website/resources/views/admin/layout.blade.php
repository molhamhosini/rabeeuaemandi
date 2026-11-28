@php($rtl = app()->getLocale() !== 'en')
@php($paused = \App\Models\Setting::ordersPaused())
@php($receipt = \App\Http\Controllers\Admin\SettingsController::receipt())
@php($ico = [
  'dashboard' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
  'board' => '<rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="10" y="4" width="5" height="11" rx="1.5"/><rect x="17" y="4" width="4" height="7" rx="1.5"/>',
  'orders' => '<path d="M8 6h12M8 12h12M8 18h12"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
  'menu' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 21V5"/><path d="M9 8h6M9 12h6"/>',
  'branches' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
  'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
  'site' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
  'lang' => '<path d="M4 5h9M8.5 3v2M6 5c0 4 3 7 6 8M11 5c0 4-3 7-6 8"/><path d="m13 21 4-9 4 9M14.5 18h5"/>',
  'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l5-5-5-5M15 12H4"/>',
  'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
  'sound' => '<path d="M11 5 6 9H3v6h3l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/>',
])
@php($svg = fn ($k) => '<svg viewBox="0 0 24 24" aria-hidden="true">'.$ico[$k].'</svg>')
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', __('site.admin.title')) | {{ __('site.brand') }}</title>
<meta name="robots" content="noindex">
<link rel="icon" href="{{ asset('images/brand/logo.png') }}">
<link href="https://fonts.googleapis.com/css2?family=Reem+Kufi:wght@600&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=7">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=1">
</head>
<body class="admin">
<div class="adm">
  <aside class="side">
    <div class="brand"><img src="{{ asset('images/brand/logo.png') }}" alt=""><span><b>{{ __('site.brand') }}</b><small>{{ __('site.admin.title') }}</small></span></div>
    <nav class="anav">
      <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>{!! $svg('dashboard') !!}<span>{{ __('admin.dashboard') }}</span></a>
      <a href="{{ route('admin.board') }}" @class(['on' => request()->routeIs('admin.board')])>{!! $svg('board') !!}<span>{{ __('admin.board') }}</span><span class="badge" data-new-badge data-zero>0</span></a>
      <a href="{{ route('admin.orders') }}" @class(['on' => request()->routeIs('admin.orders*')])>{!! $svg('orders') !!}<span>{{ __('admin.history') }}</span></a>
      <a href="{{ route('admin.menu') }}" @class(['on' => request()->routeIs('admin.menu', 'admin.items*')])>{!! $svg('menu') !!}<span>{{ __('admin.menu') }}</span></a>
      <a href="{{ route('admin.settings') }}" @class(['on' => request()->routeIs('admin.settings*')])>{!! $svg('settings') !!}<span>{{ __('admin.settings') }}</span></a>
      <a class="hide-m" href="{{ route('admin.branches') }}" @class(['on' => request()->routeIs('admin.branches*')])>{!! $svg('branches') !!}<span>{{ __('admin.branches') }}</span></a>
    </nav>
    <div class="anav foot">
      <a href="{{ route('home') }}" target="_blank">{!! $svg('site') !!}<span>{{ __('admin.view_site') }}</span></a>
      <a href="?lang={{ $rtl ? 'en' : 'ar' }}">{!! $svg('lang') !!}<span>{{ $rtl ? 'English' : 'العربية' }}</span></a>
      <form method="post" action="{{ route('admin.logout') }}">@csrf<button>{!! $svg('logout') !!}<span>{{ __('admin.logout') }}</span></button></form>
    </div>
  </aside>
  <div class="main">
    <header class="top">
      <h1>@yield('heading')</h1>
      <span class="sp"></span>
      @yield('top')
      <button type="button" class="pill-btn" data-sound title="{{ __('admin.sound_on') }}">{!! $svg('sound') !!}<span>{{ __('admin.sound_on') }}</span></button>
      <form method="post" action="{{ route('admin.pause') }}" style="margin:0">@csrf
        <input type="hidden" name="paused" value="{{ $paused ? 0 : 1 }}">
        <button class="pill-btn {{ $paused ? 'off' : 'live' }}" data-pause title="{{ $paused ? __('admin.resume') : __('admin.pause') }}"
          onclick="return {{ $paused ? 'true' : 'confirm('.e(json_encode(__('admin.pause_confirm'))).')' }}">
          <span class="dot"></span><span>{{ $paused ? __('admin.paused') : __('admin.taking') }}</span>
        </button>
      </form>
    </header>
    <main class="content">
      @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif
      @if($errors->any())<div class="flash err">{{ $errors->first() }}</div>@endif
      @yield('content')
    </main>
  </div>
</div>

{{-- new-order window: rings until the order is accepted, rejected or put off --}}
<div class="ring" data-ring role="dialog" aria-modal="true">
  <div class="box">
    <div class="hd"><span class="bell">{!! $svg('bell') !!}</span><span><b>{{ __('admin.new_order') }} <span dir="ltr" data-r-num></span></b><small data-r-sub></small></span></div>
    <div class="bd">
      <div class="meta" data-r-meta></div>
      <ul data-r-items></ul>
      <p data-r-notes style="color:#8A4B00;font-weight:700;margin:8px 0 0" hidden></p>
      <div class="tot"><span>{{ __('admin.receipt.total') }}</span><span><span data-r-total></span> {{ __('site.aed') }}</span></div>
    </div>
    <div class="ft">
      <button type="button" class="btn-s no lg" data-r-reject>{{ __('admin.reject') }}</button>
      <button type="button" class="btn-s ok lg" data-r-accept>{{ __('admin.accept') }}</button>
      <button type="button" class="later" data-r-later>{{ __('admin.later') }}</button>
    </div>
  </div>
</div>
<div class="toast-a" data-toast></div>

<script>
window.ADMIN = {
  poll: @json(route('admin.poll')),
  csrf: @json(csrf_token()),
  autoPrint: @json($receipt['auto_print']),
  t: {rejectConfirm: @json(__('admin.reject_confirm')), soundOn: @json(__('admin.sound_ready')), newToast: @json(__('admin.new_order_toast')), error: @json(__('admin.error'))},
};
</script>
<script src="{{ asset('js/admin.js') }}?v=1"></script>
@stack('scripts')
</body>
</html>
