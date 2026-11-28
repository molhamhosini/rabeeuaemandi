@extends('admin.layout')
@section('title', __('admin.menu'))
@section('heading', __('admin.menu'))
@section('top')<a class="btn-s gold" href="{{ route('admin.items.create') }}">+ {{ __('site.admin.add_item') }}</a>@endsection
@php($bn = $branches->keyBy('id'))
@section('content')
<div class="fbar"><input class="q" type="search" placeholder="{{ __('admin.search_menu') }}" data-menu-q></div>
<div class="chips">@foreach($categories as $c)<a href="#cat-{{ $c->id }}">{{ $c->tr('name') }} <small>({{ $c->items->count() }})</small></a>@endforeach</div>
@foreach($categories as $c)
  <section data-cat id="cat-{{ $c->id }}">
  <h3 style="margin:22px 0 10px">{{ $c->tr('name') }} @if($c->lead_hours)<span class="when">⏰ {{ $c->lead_hours }} {{ __('admin.set.hours_unit') }}</span>@endif</h3>
  <table class="t">
    <tbody>
    @foreach($c->items as $i)
    <tr data-href="{{ route('admin.items.edit', $i) }}" data-name="{{ mb_strtolower($i->name_ar.' '.$i->name_en) }}">
      <td style="width:70px">@if($i->image)<img class="thumb" src="{{ $i->imageUrl() }}" alt="">@endif</td>
      <td><b>{{ $i->name_ar }}</b><br><small>{{ $i->name_en }}</small>
        @if($i->branchList())<br>@foreach($i->branchList() as $bid)<span class="branch-tag">{{ $bn->get($bid)?->tr('name') }}</span> @endforeach @endif</td>
      <td>{{ $i->price !== null ? \App\Services\OrderService::money($i->price) : $i->options->map(fn ($o) => $o->tr('name').' '.\App\Services\OrderService::money($o->price))->join(' · ') }}</td>
      <td>@if($i->is_featured)<span class="st">★</span>@endif</td>
      <td><form method="post" action="{{ route('admin.items.toggle', $i) }}" style="margin:0">@csrf @method('patch')<button class="st {{ $i->is_active ? 'st-paid' : 'st-cancelled' }}" style="border:0;cursor:pointer">{{ $i->is_active ? __('site.admin.active') : __('site.admin.hidden') }}</button></form></td>
      <td><a class="link" href="{{ route('admin.items.edit', $i) }}">{{ __('site.admin.edit') }}</a></td>
    </tr>
    @endforeach
    </tbody>
  </table>
  </section>
@endforeach
@push('scripts')
<script>
  document.querySelector('[data-menu-q]').addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('[data-cat]').forEach(sec => {
      let any = false;
      sec.querySelectorAll('tr[data-name]').forEach(tr => { const on = !q || tr.dataset.name.includes(q); tr.hidden = !on; any ||= on; });
      sec.hidden = !any;
    });
  });
</script>
@endpush
@endsection
