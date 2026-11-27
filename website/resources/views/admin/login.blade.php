@php($rtl = app()->getLocale() !== 'en')
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ __('site.admin.login') }}</title><meta name="robots" content="noindex">
<link href="https://fonts.googleapis.com/css2?family=Reem+Kufi:wght@600&family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/site.css') }}?v=1"></head>
<body class="pattern" style="min-height:100vh;display:grid;place-items:center;padding:20px">
<form method="post" class="card" style="width:min(380px,100%)">
  @csrf
  <div style="text-align:center;margin-bottom:16px"><img src="{{ asset('images/brand/logo.png') }}" alt="" style="width:90px;margin:0 auto"><h1 style="font-size:1.4rem;margin-top:8px">{{ __('site.admin.title') }}</h1></div>
  <div class="field"><label>{{ __('site.admin.email') }}</label><input name="email" type="email" value="{{ old('email') }}" required dir="ltr"></div>
  <div class="field"><label>{{ __('site.admin.password') }}</label><input name="password" type="password" required dir="ltr"></div>
  @error('email')<p class="err">{{ $message }}</p>@enderror
  <button class="btn btn-primary btn-block">{{ __('site.admin.login') }}</button>
</form>
</body></html>
