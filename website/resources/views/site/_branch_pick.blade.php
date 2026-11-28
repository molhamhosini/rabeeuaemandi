@if(($bs = \App\Models\Branch::active()->get())->count() > 1)
<div class="bpick" data-bpick>
  <span>{{ __('site.pick_branch') }}:</span>
  @foreach($bs as $b)<button type="button" data-branch-btn="{{ $b->id }}">{{ $b->tr('name') }}</button>@endforeach
</div>
@endif
