@extends('admin.layout')
@section('title', __('admin.branches'))
@section('heading', __('admin.branches'))
@section('content')
@foreach($branches as $b)
<form class="card" method="post" action="{{ route('admin.branches.update', $b) }}" style="max-width:860px">
  @csrf @method('put')
  <h3>{{ $b->tr('name') }}</h3>
  <div class="two">
    <div class="field"><label>{{ __('site.admin.name_ar') }}</label><input name="name_ar" value="{{ $b->name_ar }}"></div>
    <div class="field"><label>{{ __('site.admin.name_en') }}</label><input name="name_en" value="{{ $b->name_en }}" dir="ltr"></div>
    <div class="field"><label>{{ __('site.admin.address_ar') }}</label><input name="address_ar" value="{{ $b->address_ar }}"></div>
    <div class="field"><label>{{ __('site.admin.address_en') }}</label><input name="address_en" value="{{ $b->address_en }}" dir="ltr"></div>
    <div class="field"><label>{{ __('site.admin.whatsapp_number') }}</label><input name="whatsapp" value="{{ $b->whatsapp }}" dir="ltr" placeholder="9715XXXXXXXX"></div>
    <div class="field"><label>{{ __('site.admin.phones') }}</label><input name="phones" value="{{ implode(', ', $b->phones ?? []) }}" dir="ltr"></div>
    <div class="field"><label>{{ __('site.admin.hours_ar') }}</label><input name="hours_ar" value="{{ $b->hours_ar }}"></div>
    <div class="field"><label>{{ __('site.admin.hours_en') }}</label><input name="hours_en" value="{{ $b->hours_en }}" dir="ltr"></div>
    <div class="field"><label>{{ __('site.admin.delivery_fee') }}</label><input name="delivery_fee" type="number" step="0.5" value="{{ $b->delivery_fee }}" dir="ltr"></div>
    <div class="field"><label>{{ __('site.admin.min_order') }}</label><input name="min_order" type="number" step="1" value="{{ $b->min_order }}" dir="ltr"></div>
  </div>
  <div class="field"><label>{{ __('site.admin.map') }}</label><input name="map_url" value="{{ $b->map_url }}" dir="ltr"></div>
  <div class="two">
    <div class="field"><label>{{ __('admin.branch_lat') }}</label><input name="lat" value="{{ $b->lat }}" dir="ltr" placeholder="24.4890"></div>
    <div class="field"><label>{{ __('admin.branch_lng') }}</label><input name="lng" value="{{ $b->lng }}" dir="ltr" placeholder="54.3560"></div>
  </div>
  <div class="field">
    <label>{{ __('admin.areas') }}</label>
    <textarea name="areas" rows="8" style="width:100%">{{ $b->areas->where('is_active', true)->map(fn ($a) => $a->name_ar.' | '.$a->name_en)->implode("\n") }}</textarea>
    <small style="color:#7A6E60">{{ __('admin.areas_help') }}</small>
  </div>
  <label style="display:flex;gap:8px;margin:6px 0 14px"><input type="checkbox" name="is_active" value="1" @checked($b->is_active)> {{ __('site.admin.active') }}</label>
  <button class="btn btn-primary">{{ __('site.admin.save') }}</button>
</form>
@endforeach
@endsection
