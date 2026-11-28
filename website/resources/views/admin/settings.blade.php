@extends('admin.layout')
@section('title', __('admin.settings'))
@section('heading', __('admin.settings'))
@php($times = collect(range(0, 47))->map(fn ($n) => sprintf('%02d:%02d', intdiv($n, 2), $n % 2 * 30)))
@php($label = fn ($t) => \Carbon\Carbon::createFromFormat('H:i', $t)->locale(app()->getLocale())->translatedFormat('g:i A'))
@section('content')
<form method="post" action="{{ route('admin.settings.update') }}" style="max-width:820px">
  @csrf @method('put')

  <div class="card set-sec">
    <h3>{{ __('admin.set.hours') }}</h3>
    <p class="sub">{{ __('admin.set.hours_sub') }}</p>
    <div class="two">
      <div class="field"><label>{{ __('admin.set.open') }}</label><select name="open">@foreach($times as $t)<option value="{{ $t }}" @selected(old('open', $hours['open']) === $t)>{{ $label($t) }}</option>@endforeach</select></div>
      <div class="field"><label>{{ __('admin.set.last') }}</label><select name="last">@foreach($times as $t)<option value="{{ $t }}" @selected(old('last', $hours['last']) === $t)>{{ $label($t) }}</option>@endforeach</select></div>
      <div class="field"><label>{{ __('admin.set.meal_minutes') }}</label><input type="number" name="meal_minutes" min="0" max="240" step="5" value="{{ old('meal_minutes', $hours['meal_minutes']) }}" dir="ltr"><small style="color:var(--muted)">{{ __('admin.set.meal_hint') }}</small></div>
      <div class="field"><label>{{ __('admin.set.book_days') }}</label><input type="number" name="book_days" min="0" max="90" value="{{ old('book_days', $hours['book_days']) }}" dir="ltr"><small style="color:var(--muted)">{{ __('admin.set.book_hint') }}</small></div>
    </div>
  </div>

  <div class="card set-sec">
    <h3>{{ __('admin.set.lead') }}</h3>
    <p class="sub">{{ __('admin.set.lead_sub') }}</p>
    @foreach($categories as $c)
      <div class="lead-row"><b>{{ $c->tr('name') }}</b>
        <span><input type="number" name="lead[{{ $c->id }}]" min="0" max="72" value="{{ old('lead.'.$c->id, (int) $c->lead_hours) }}" dir="ltr"> {{ __('admin.set.hours_unit') }}</span></div>
    @endforeach
  </div>

  <div class="card set-sec">
    <h3>{{ __('admin.set.pay') }}</h3>
    <p class="sub">{{ __('admin.set.pay_sub') }}</p>
    @foreach(['cash', 'whatsapp', 'card'] as $mth)
      <div class="sw">
        <span><b>{{ __('admin.set.pay_'.$mth) }}</b><small>{{ __('admin.set.pay_'.$mth.'_sub') }} @if($mth === 'card' && ! $stripe)<span style="color:#9B2A1E">{{ __('admin.set.stripe_missing') }}</span>@endif</small></span>
        <label class="toggle"><input type="checkbox" name="pay[{{ $mth }}]" value="1" @checked(old('pay.'.$mth, $pay[$mth]))><i></i></label>
      </div>
    @endforeach
  </div>

  <div class="card set-sec">
    <h3>{{ __('admin.set.receipt') }}</h3>
    <div class="two">
      <div class="field"><label>{{ __('admin.set.paper') }}</label><select name="paper">@foreach([80, 58] as $w)<option value="{{ $w }}" @selected((int) old('paper', $receipt['paper']) === $w)>{{ $w }} mm</option>@endforeach</select></div>
      <div class="field"><label>{{ __('admin.set.footer') }}</label><input name="receipt_footer" maxlength="200" value="{{ old('receipt_footer', $receipt['footer']) }}" placeholder="{{ __('admin.set.footer_ph') }}"></div>
    </div>
    <div class="sw"><span><b>{{ __('admin.set.auto_print') }}</b></span>
      <label class="toggle"><input type="checkbox" name="auto_print" value="1" @checked(old('auto_print', $receipt['auto_print']))><i></i></label></div>
    @if($sample = \App\Models\Order::latest('id')->first())
      <p style="margin:10px 0 0"><button type="button" class="btn-s ghost" data-print="{{ route('admin.orders.ticket', $sample) }}">{{ __('admin.set.test_print') }}</button></p>
    @endif
  </div>

  <button class="btn btn-primary">{{ __('site.admin.save') }}</button>
</form>
@endsection
