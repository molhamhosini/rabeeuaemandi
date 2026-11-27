@extends('layouts.site')
@section('title', __('site.checkout.title'))
@section('content')
<div class="wrap">
  <form class="co" data-checkout data-action="{{ route('checkout.store') }}" novalidate>
    <div>
      <h1 style="font-size:2rem;margin-bottom:18px">{{ __('site.checkout.title') }}</h1>
      <div class="card">
        <h3>{{ __('site.checkout.branch') }}</h3>
        <div class="seg">
          @foreach($branches as $i => $b)
          <label><input type="radio" name="branch_id" value="{{ $b->id }}" @checked($i === 0)><span><b>{{ $b->tr('name') }}</b><small>{{ $b->tr('address') }}</small></span></label>
          @endforeach
        </div>
      </div>
      <div class="card">
        <h3>{{ __('site.checkout.fulfillment') }}</h3>
        <div class="seg" style="margin-bottom:16px">
          <label><input type="radio" name="fulfillment" value="delivery" checked><span><b>{{ __('site.checkout.delivery') }}</b><small>{{ __('site.free_delivery') }}</small></span></label>
          <label><input type="radio" name="fulfillment" value="pickup"><span><b>{{ __('site.checkout.pickup') }}</b><small>{{ __('site.order.payments.unpaid') }}</small></span></label>
        </div>
        <div class="field"><label for="n">{{ __('site.checkout.name') }}</label><input id="n" name="customer_name" autocomplete="name" required></div>
        <div class="field"><label for="p">{{ __('site.checkout.phone') }}</label><input id="p" name="customer_phone" type="tel" inputmode="tel" dir="ltr" placeholder="05X XXX XXXX" autocomplete="tel" required></div>
        <div class="field" data-address><label for="a">{{ __('site.checkout.address') }}</label><textarea id="a" name="address" rows="2" placeholder="{{ __('site.checkout.address_ph') }}"></textarea></div>
        <div class="field"><label for="o">{{ __('site.checkout.notes') }}</label><textarea id="o" name="notes" rows="2" placeholder="{{ __('site.checkout.notes_ph') }}"></textarea></div>
      </div>
      <div class="card" data-when>
        <h3>{{ __('site.schedule.title') }}</h3>
        <div class="seg">
          <label data-asap><input type="radio" name="when" value="now" checked><span><b>{{ __('site.schedule.asap') }}</b><small>{{ __('site.schedule.asap_sub') }}</small></span></label>
          <label><input type="radio" name="when" value="later"><span><b>{{ __('site.schedule.later') }}</b><small>{{ __('site.schedule.later_sub') }}</small></span></label>
        </div>
        <p class="lead-note" data-lead-note hidden></p>
        <div class="field" data-when-at hidden style="margin-top:14px"><label for="w">{{ __('site.schedule.at') }}</label><input id="w" type="datetime-local" name="scheduled_at" step="900" dir="ltr"></div>
      </div>
      <div class="card">
        <h3>{{ __('site.checkout.payment') }}</h3>
        <div class="seg">
          <label @class(['disabled' => ! $cardEnabled])><input type="radio" name="payment_method" value="card" @checked($cardEnabled) @disabled(! $cardEnabled)><span><b>{{ __('site.checkout.pay_card') }}</b><small>{{ $cardEnabled ? __('site.checkout.pay_card_sub') : __('site.card_unavailable') }}</small></span></label>
          <label><input type="radio" name="payment_method" value="whatsapp" @checked(! $cardEnabled)><span><b>{{ __('site.checkout.pay_whatsapp') }}</b><small>{{ __('site.checkout.pay_whatsapp_sub') }}</small></span></label>
        </div>
      </div>
    </div>
    <aside class="summary">
      <div class="card">
        <h3>{{ __('site.checkout.summary') }}</h3>
        <div data-cart-lines data-compact></div>
        <div class="sum" style="margin-top:10px"><span>{{ __('site.cart.subtotal') }}</span><span><span data-cart-total>0</span> {{ __('site.aed') }}</span></div>
        <div class="sum"><span>{{ __('site.cart.delivery') }}</span><span>{{ __('site.cart.free') }}</span></div>
        <div class="sum total"><span>{{ __('site.cart.total') }}</span><span><span data-cart-total>0</span> {{ __('site.aed') }}</span></div>
        <button class="btn btn-primary btn-block" style="margin-top:14px" type="submit" data-submit
          data-card="{{ __('site.checkout.submit_card') }}" data-wa="{{ __('site.checkout.submit_whatsapp') }}"></button>
        <p class="err" data-err hidden></p>
      </div>
    </aside>
  </form>
</div>
@endsection
