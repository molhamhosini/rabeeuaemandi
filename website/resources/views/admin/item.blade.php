@extends('admin.layout')
@section('title', $item->exists ? $item->name_ar : __('site.admin.add_item'))
@section('heading', $item->exists ? __('site.admin.edit').': '.$item->tr('name') : __('site.admin.add_item'))
@section('content')
<p style="margin:0 0 12px"><a class="link" href="{{ route('admin.menu') }}">← {{ __('admin.menu') }}</a></p>
<form class="card" method="post" enctype="multipart/form-data" action="{{ $item->exists ? route('admin.items.update', $item) : route('admin.items.store') }}" style="max-width:760px">
  @csrf @if($item->exists) @method('put') @endif
  <div class="field"><label>{{ __('site.admin.category') }}</label><select name="category_id">@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $item->category_id) == $c->id)>{{ $c->tr('name') }}</option>@endforeach</select></div>
  <div class="two">
    <div class="field"><label>{{ __('site.admin.name_ar') }}</label><input name="name_ar" value="{{ old('name_ar', $item->name_ar) }}" required></div>
    <div class="field"><label>{{ __('site.admin.name_en') }}</label><input name="name_en" value="{{ old('name_en', $item->name_en) }}" dir="ltr" required></div>
    <div class="field"><label>{{ __('site.admin.desc_ar') }}</label><input name="description_ar" value="{{ old('description_ar', $item->description_ar) }}"></div>
    <div class="field"><label>{{ __('site.admin.desc_en') }}</label><input name="description_en" value="{{ old('description_en', $item->description_en) }}" dir="ltr"></div>
  </div>
  @php($on = old('branches', $item->branch_ids ?: $branches->pluck('id')->all()))
  <div class="field"><label>{{ __('admin.available_at') }}</label>
    <div style="display:flex;gap:18px;flex-wrap:wrap">
      @foreach($branches as $b)
        <label style="display:flex;gap:6px;align-items:center;font-weight:700"><input type="checkbox" name="branches[]" value="{{ $b->id }}" @checked(in_array($b->id, array_map('intval', (array) $on)))> {{ $b->tr('name') }}</label>
      @endforeach
    </div>
    <small style="color:var(--muted)">{{ __('admin.available_hint') }}</small>
  </div>
  <div class="field"><label>{{ __('site.admin.price') }} ({{ __('site.aed') }})</label><input name="price" type="number" step="0.01" min="0" value="{{ old('price', $item->price) }}" dir="ltr"><small style="color:var(--muted)">{{ __('site.admin.price_hint') }}</small></div>
  <div class="field"><label>{{ __('site.admin.options') }}</label><small style="color:var(--muted)">{{ __('site.admin.option_hint') }}</small>
    @php($opts = $item->options ?? collect())
    @for($n = 0; $n < max(4, $opts->count() + 2); $n++)
      @php($o = $opts[$n] ?? null)
      <div class="two" style="grid-template-columns:1fr 1fr 120px;margin-top:6px">
        <input name="options[{{ $n }}][name_ar]" placeholder="{{ __('site.admin.name_ar') }}" value="{{ $o?->name_ar }}">
        <input name="options[{{ $n }}][name_en]" placeholder="{{ __('site.admin.name_en') }}" value="{{ $o?->name_en }}" dir="ltr">
        <input name="options[{{ $n }}][price]" type="number" step="0.01" placeholder="{{ __('site.admin.price') }}" value="{{ $o?->price }}" dir="ltr">
      </div>
    @endfor
  </div>
  <div class="field"><label>{{ __('site.admin.addon_groups') }}</label><small style="color:var(--muted)">{{ __('site.admin.addon_hint') }}</small>
    @php($groups = $item->addonGroups ?? collect())
    @for($n = 0; $n < $groups->count() + 2; $n++)
      @php($g = $groups[$n] ?? null)
      <div style="border:1px solid #EADCBD;border-radius:12px;padding:10px;margin-top:8px">
        <div class="two">
          <input name="groups[{{ $n }}][name_ar]" placeholder="{{ __('site.admin.group_name_ar') }}" value="{{ $g?->name_ar }}">
          <input name="groups[{{ $n }}][name_en]" placeholder="{{ __('site.admin.group_name_en') }}" value="{{ $g?->name_en }}" dir="ltr">
        </div>
        <div style="display:flex;gap:18px;margin:8px 0">
          <label style="display:flex;gap:6px"><input type="checkbox" name="groups[{{ $n }}][required]" value="1" @checked($g?->required)> {{ __('site.required') }}</label>
          <label style="display:flex;gap:6px"><input type="checkbox" name="groups[{{ $n }}][multiple]" value="1" @checked($g?->multiple)> {{ __('site.admin.multiple') }}</label>
        </div>
        <textarea name="groups[{{ $n }}][lines]" rows="4" placeholder="{{ __('site.admin.addon_lines_ph') }}" style="width:100%">{{ $g ? $g->addons->map(fn ($a) => $a->name_ar.' | '.$a->name_en.' | '.\App\Services\OrderService::money($a->price))->join("\n") : '' }}</textarea>
      </div>
    @endfor
  </div>
  <div class="field"><label>{{ __('site.admin.image') }}</label>@if($item->image)<img class="thumb" style="width:120px;height:90px" src="{{ $item->imageUrl() }}" alt="">@endif<input type="file" name="image" accept="image/*"></div>
  <label style="display:flex;gap:8px;margin:6px 0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> {{ __('site.admin.active') }}</label>
  <label style="display:flex;gap:8px;margin:6px 0 16px"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured))> {{ __('site.admin.featured') }}</label>
  <button class="btn btn-primary">{{ __('site.admin.save') }}</button>
</form>
@endsection
