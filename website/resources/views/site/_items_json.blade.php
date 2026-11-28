@php
  $itemsJson = [];
  foreach ($items as $i) {
      $itemsJson[$i->id] = [
          'id' => $i->id,
          'name' => $i->tr('name'),
          'price' => $i->price !== null ? (float) $i->price : null,
          'image' => $i->imageUrl(),
          'desc' => $i->tr('description'),
          'lead' => (int) $i->category?->lead_hours,
          'branches' => $i->branchList(),
          'options' => $i->options->map(fn ($o) => ['id' => $o->id, 'name' => $o->tr('name'), 'price' => (float) $o->price])->values()->all(),
          'groups' => $i->addonGroups->map(fn ($g) => [
              'id' => $g->id, 'name' => $g->tr('name'), 'multiple' => $g->multiple, 'required' => $g->required,
              'addons' => $g->addons->map(fn ($a) => ['id' => $a->id, 'name' => $a->tr('name'), 'price' => (float) $a->price])->values()->all(),
          ])->values()->all(),
      ];
  }
@endphp
<script>window.ITEMS = Object.assign(window.ITEMS || {}, {!! json_encode((object) $itemsJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!});</script>
