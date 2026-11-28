@php($rtl = app()->getLocale() !== 'en')
@php($en = ! $rtl)
@php($r = \App\Http\Controllers\Admin\SettingsController::receipt())
@php($w = $r['paper'])
@php($kitchen = request('copy') === 'kitchen')
@php($m = fn ($v) => \App\Services\OrderService::money($v))
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>#{{ $order->number }}</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700;800&display=swap" rel="stylesheet">
<style>
  @page{size:{{ $w }}mm auto;margin:0}
  *{box-sizing:border-box}
  html,body{margin:0;background:#eee;color:#000;font-family:Tajawal,Tahoma,Arial,sans-serif;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .paper{width:{{ $w }}mm;margin:12px auto;background:#fff;padding:{{ $w == 58 ? '3mm 2.5mm' : '4mm 4mm' }};font-size:{{ $w == 58 ? '11px' : '13px' }};line-height:1.35;font-weight:700}
  .c{text-align:center}
  .logo{width:{{ $w == 58 ? '18mm' : '24mm' }};filter:grayscale(1) contrast(1.4)}
  h1{font-size:1.25em;margin:2px 0 0}
  .sm{font-size:.86em;font-weight:500}
  .num{font-size:2.1em;font-weight:800;letter-spacing:1px;margin:4px 0 0;direction:ltr}
  .tag{display:inline-block;border:2px solid #000;padding:1px 8px;font-weight:800;margin:3px 0;font-size:1.05em}
  .tag.inv{background:#000;color:#fff}
  hr{border:0;border-top:1.5px dashed #000;margin:6px 0}
  hr.solid{border-top-style:solid;border-top-width:2px}
  table{width:100%;border-collapse:collapse}
  td{vertical-align:top;padding:2px 0}
  td.q{width:2.4em;font-weight:800;font-size:1.1em}
  td.p{text-align:end;white-space:nowrap;padding-inline-start:4px}
  .det{display:block;font-weight:500;font-size:.88em;padding-inline-start:.4em}
  .row{display:flex;justify-content:space-between;gap:8px}
  .tot{font-size:1.3em;font-weight:800}
  .kv{display:grid;grid-template-columns:auto 1fr;gap:1px 6px}
  .kv span:nth-child(odd){font-weight:500}
  .note{border:2px solid #000;padding:4px 6px;margin-top:4px}
  .bar{display:flex;gap:8px;justify-content:center;margin:10px auto;font-family:Tajawal,sans-serif}
  .bar a,.bar button{border:0;border-radius:10px;padding:9px 16px;font:inherit;font-weight:800;background:#4A321C;color:#fff;text-decoration:none;cursor:pointer}
  .bar a.alt{background:#fff;color:#4A321C;border:1px solid #ccc}
  @media print{html,body{background:#fff}.paper{margin:0;width:100%}.bar{display:none}}
</style>
</head>
<body>
<div class="bar">
  <button onclick="window.print()">{{ __('admin.receipt.print') }}</button>
  <a class="alt" href="?copy={{ $kitchen ? 'customer' : 'kitchen' }}">{{ $kitchen ? __('admin.receipt.customer_copy') : __('admin.receipt.kitchen_copy') }}</a>
  <a class="alt" href="#" onclick="window.close();return false">{{ __('admin.receipt.close') }}</a>
</div>
<div class="paper">
  <div class="c">
    <img class="logo" src="{{ asset('images/brand/logo.png') }}" alt="">
    <h1>{{ __('site.brand') }}</h1>
    <div class="sm">{{ $order->branch->tr('name') }}@if($ph = collect($order->branch->phones)->first()) · <span dir="ltr">{{ $ph }}</span>@endif</div>
    <hr class="solid">
    @if($kitchen)<div class="tag inv">{{ __('admin.receipt.kitchen_copy') }}</div>@endif
    <div class="num">#{{ $order->number }}</div>
    <div class="tag {{ $order->fulfillment === 'delivery' ? 'inv' : '' }}">{{ __('admin.'.($order->fulfillment === 'delivery' ? 'delivery' : 'pickup')) }}</div>
    <div class="sm" dir="ltr">{{ $order->created_at->copy()->timezone(\App\Services\OrderService::TZ)->format('d/m/Y  h:i A') }}</div>
    @if($order->scheduled_at)<div style="margin-top:3px">{{ __('admin.when') }}: <b>{{ $order->whenText() }}</b></div>@endif
  </div>
  <hr>
  <table>
    @foreach($order->items as $i)
      <tr>
        <td class="q">{{ $i->quantity }}×</td>
        <td>{{ $en ? $i->name_en : $i->name_ar }}
          @if($d = $en ? $i->details_en : $i->details_ar)
            @foreach(explode(' · ', $d) as $part)<span class="det">- {{ $part }}</span>@endforeach
          @endif
        </td>
        @unless($kitchen)<td class="p">{{ $m($i->line_total) }}</td>@endunless
      </tr>
    @endforeach
  </table>
  @if($order->notes)<div class="note">{{ __('admin.notes') }}: {{ $order->notes }}</div>@endif
  @unless($kitchen)
    <hr>
    <div class="row"><span>{{ __('admin.receipt.subtotal') }}</span><span>{{ $m($order->subtotal) }}</span></div>
    @if((float) $order->delivery_fee > 0)<div class="row"><span>{{ __('admin.receipt.delivery_fee') }}</span><span>{{ $m($order->delivery_fee) }}</span></div>@endif
    <div class="row tot"><span>{{ __('admin.receipt.total') }}</span><span>{{ $m($order->total) }} {{ __('site.aed') }}</span></div>
    <div class="row sm"><span>{{ __('admin.payment') }}</span><span>{{ __('admin.receipt.pay_'.$order->payment_method) }} · {{ $order->payment_status === 'paid' ? __('admin.receipt.paid') : __('admin.receipt.unpaid') }}</span></div>
  @endunless
  <hr>
  <div class="kv">
    <span>{{ __('admin.receipt.customer') }}</span><span>{{ $order->customer_name }}</span>
    <span>{{ __('admin.receipt.phone') }}</span><span dir="ltr" style="text-align:start">{{ $order->customer_phone }}</span>
    @if($order->fulfillment === 'delivery' && $order->address)<span>{{ __('admin.address') }}</span><span>{{ $order->address }}</span>@endif
  </div>
  @unless($kitchen)
    <hr>
    <div class="c sm">{{ $r['footer'] ?: __('admin.receipt.thanks') }}<br>rabeeuaemandi.com</div>
  @endunless
</div>
@if(request('auto'))
<script>
  // opened by "accept" or the print button: print once the logo has loaded, then close the window
  window.addEventListener('load', () => { window.print(); });
  window.addEventListener('afterprint', () => { if (window.opener) window.close(); });
</script>
@endif
</body>
</html>
