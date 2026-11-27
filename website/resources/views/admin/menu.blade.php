@extends('admin.layout')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px"><h1 style="margin:0">{{ __('site.admin.menu') }}</h1><a class="btn btn-primary mini" href="{{ route('admin.items.create') }}">+ {{ __('site.admin.add_item') }}</a></div>
@foreach($categories as $c)
  <h3 style="margin:22px 0 10px">{{ $c->tr('name') }}</h3>
  <div class="tbl"><table>
    @foreach($c->items as $i)
    <tr>
      <td style="width:70px">@if($i->image)<img class="thumb" src="{{ $i->imageUrl() }}" alt="">@endif</td>
      <td><b>{{ $i->name_ar }}</b><br><small style="color:var(--muted)">{{ $i->name_en }}</small></td>
      <td>{{ $i->price !== null ? \App\Services\OrderService::money($i->price) : $i->options->map(fn ($o) => $o->tr('name').' '.\App\Services\OrderService::money($o->price))->join(' · ') }}</td>
      <td>@if($i->is_featured)<span class="st">★</span>@endif</td>
      <td><form method="post" action="{{ route('admin.items.toggle', $i) }}">@csrf @method('patch')<button class="st {{ $i->is_active ? 'st-paid' : 'st-cancelled' }}" style="border:0">{{ $i->is_active ? __('site.admin.active') : __('site.admin.hidden') }}</button></form></td>
      <td><a class="link" href="{{ route('admin.items.edit', $i) }}">{{ __('site.admin.edit') }}</a></td>
    </tr>
    @endforeach
  </table></div>
@endforeach
@endsection
