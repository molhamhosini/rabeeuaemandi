@php($rtl = app()->getLocale() !== 'en')
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('site.admin.title') }} | {{ __('site.brand') }}</title>
<meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Reem+Kufi:wght@600&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=1">
<style>
 .adm{display:grid;grid-template-columns:230px 1fr;min-height:100vh}
 .side{background:var(--brown);color:#F2E3C4;padding:20px 14px;display:flex;flex-direction:column;gap:4px}
 .side .logo{margin-bottom:18px}.side .logo b{color:#fff}
 .side a,.side button{display:block;padding:10px 14px;border-radius:12px;color:#F2E3C4;background:none;border:0;text-align:start;width:100%;font-weight:500}
 .side a.on,.side a:hover{background:rgba(255,255,255,.12);color:#fff}
 .main{padding:26px;min-width:0}
 .main h1{font-size:1.7rem;margin-bottom:18px}
 .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
 .stat{background:var(--white);border:1px solid var(--line);border-radius:16px;padding:16px}
 .stat b{display:block;font-size:1.6rem;color:var(--brown)}.stat span{color:var(--muted);font-size:.9rem}
 table{width:100%;border-collapse:collapse;background:var(--white);border:1px solid var(--line);border-radius:16px;overflow:hidden}
 th,td{padding:11px 12px;text-align:start;border-bottom:1px solid var(--line);font-size:.93rem;vertical-align:middle}
 th{background:var(--sand);color:var(--brown);font-weight:700}
 .tbl{overflow-x:auto}
 .st{display:inline-block;padding:3px 10px;border-radius:999px;font-size:.8rem;font-weight:700;background:var(--sand)}
 .st-new{background:#FDE7C8;color:#8A4B00}.st-paid,.st-completed{background:#DDF3E1;color:#1D6B32}.st-cancelled,.st-failed{background:#F8DADA;color:#8B1F1F}
 .flash{background:#DDF3E1;color:#1D6B32;padding:10px 14px;border-radius:12px;margin-bottom:14px;font-weight:700}
 .filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
 .filters a{padding:6px 14px;border-radius:999px;border:1px solid var(--line);background:var(--white);font-weight:700;color:var(--brown);font-size:.9rem}
 .filters a.on{background:var(--brown);color:#fff}
 .thumb{width:54px;height:42px;object-fit:contain;background:var(--sand);border-radius:8px}
 .mini{padding:6px 12px;font-size:.85rem;border-radius:999px}
 .two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
 @media (max-width:860px){.adm{grid-template-columns:1fr}.side{flex-direction:row;flex-wrap:wrap;padding:10px}.side .logo{display:none}.side a,.side button{width:auto}.stats{grid-template-columns:1fr}.two{grid-template-columns:1fr}.main{padding:16px}}
</style>
</head>
<body>
<div class="adm">
  <aside class="side">
    <div class="logo"><img src="{{ asset('images/brand/logo.png') }}" alt=""><span><b>{{ __('site.admin.title') }}</b></span></div>
    <a href="{{ route('admin.orders') }}" @class(['on' => request()->routeIs('admin.orders*')])>{{ __('site.admin.orders') }}</a>
    <a href="{{ route('admin.menu') }}" @class(['on' => request()->routeIs('admin.menu', 'admin.items*')])>{{ __('site.admin.menu') }}</a>
    <a href="{{ route('admin.branches') }}" @class(['on' => request()->routeIs('admin.branches*')])>{{ __('site.admin.branches') }}</a>
    <a href="{{ route('home') }}" target="_blank">{{ __('site.admin.view_site') }}</a>
    <a href="?lang={{ $rtl ? 'en' : 'ar' }}">{{ $rtl ? 'English' : 'العربية' }}</a>
    <form method="post" action="{{ route('admin.logout') }}">@csrf<button>{{ __('site.admin.logout') }}</button></form>
  </aside>
  <main class="main">
    @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="flash" style="background:#F8DADA;color:#8B1F1F">{{ $errors->first() }}</div>@endif
    @yield('content')
  </main>
</div>
</body>
</html>
