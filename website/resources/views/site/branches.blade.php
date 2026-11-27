@extends('layouts.site')
@section('title', __('site.nav.branches'))
@section('content')
<section class="menu-hero"><div class="wrap"><span class="eyebrow light">{{ __('site.brand') }}</span><h1>{{ __('site.our_branches') }}</h1><i class="orn"></i><p>{{ __('site.branches_sub') }}</p></div></section>
<section class="section" style="padding-top:36px">
  <div class="wrap">
    @include('site._branches', ['branches' => $branches])
    <div class="mosaic" style="margin-top:28px">
      <img src="{{ asset('images/brand/storefront.webp') }}" alt="" loading="lazy">
      <img src="{{ asset('images/brand/interior-1.webp') }}" alt="" loading="lazy">
      <img src="{{ asset('images/brand/interior-2.webp') }}" alt="" loading="lazy">
    </div>
  </div>
</section>
@endsection
