@php($hasOpts = $item->options->isNotEmpty())
@php($hasChoices = $item->hasChoices())
@php($only = $item->branchList())
@php($bnames = once(fn () => \App\Models\Branch::all()->mapWithKeys(fn ($b) => [$b->id => $b->tr('name')])))
<article class="dish" data-dish="{{ $item->id }}" @if($only) data-branches="{{ implode(',', $only) }}" @endif>
  <div @class(['ph', 'photo' => str_contains((string) $item->image, 'photos/'), 'menu' => str_contains((string) $item->image, 'images/menu/'), 'emblem' => str_contains((string) $item->image, 'emblem')])>
    @if($item->image)
      <img src="{{ $item->imageUrl() }}" alt="{{ $item->tr('name') }}" loading="lazy">
    @else
      <svg class="noimg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 3h10l-1 18H8z"/><path d="M7.5 8h9"/></svg>
    @endif
    @if($item->is_featured && ! request()->routeIs('home'))<span class="tag">{{ __('site.featured') }}</span>@endif
  </div>
  <div class="body">
    <h3>{{ $item->tr('name') }}</h3>
    <div class="alt">{{ app()->getLocale() === 'en' ? $item->name_ar : $item->name_en }}</div>
    @if($only)<div class="only-at">📍 {{ __('site.only_at', ['branch' => collect($only)->map(fn ($id) => $bnames[$id] ?? '')->filter()->join(' / ')]) }}</div>@endif
    @if($item->tr('description'))<div class="desc">{{ $item->tr('description') }}</div>@endif
    <div class="row">
      <span class="price">@if($hasOpts)<small>{{ __('site.from') }} </small>@endif{{ \App\Services\OrderService::money($item->displayPrice()) }} <small>{{ __('site.aed') }}</small></span>
      <button class="add" data-add="{{ $item->id }}" aria-label="{{ $hasChoices ? __('site.choose') : __('site.add') }} {{ $item->tr('name') }}" title="{{ $hasChoices ? __('site.choose') : __('site.add') }}"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></button>
    </div>
  </div>
</article>
