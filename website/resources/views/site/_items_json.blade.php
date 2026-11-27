@php
  $itemsJson = [];
  foreach ($items as $i) {
      $itemsJson[$i->id] = [
          'id' => $i->id,
          'name' => $i->tr('name'),
          'price' => $i->price !== null ? (float) $i->price : null,
          'image' => $i->imageUrl(),
          'desc' => $i->tr('description'),
          'options' => $i->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->tr('name'), 'price' => (float) $o->price])->values()->all(),
      ];
  }
@endphp
<script>window.ITEMS = Object.assign(window.ITEMS || {}, {!! json_encode((object) $itemsJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!});</script>
