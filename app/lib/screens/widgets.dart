import 'package:material_ui/material_ui.dart';

import '../app_state.dart';
import '../models.dart';
import '../strings.dart';
import '../theme.dart';

class DishImage extends StatelessWidget {
  final MenuItem item;
  final double? height;
  const DishImage(this.item, {super.key, this.height});

  @override
  Widget build(BuildContext context) {
    final bg = Container(
      height: height,
      decoration: const BoxDecoration(gradient: RadialGradient(colors: [Color(0xFFFFF6E2), Color(0xFFF6E5BF)], radius: .9)),
    );
    if (item.image == null) {
      return Stack(fit: StackFit.passthrough, children: [bg, const Center(child: Icon(Icons.local_drink_outlined, size: 48, color: Brand.gold))]);
    }
    if (item.isPhoto) {
      return Image.network(item.image!, height: height, width: double.infinity, fit: BoxFit.cover,
          errorBuilder: (_, _, _) => bg);
    }
    return Stack(fit: StackFit.passthrough, children: [
      bg,
      Padding(
        padding: const EdgeInsets.all(10),
        child: Image.network(item.image!, fit: BoxFit.contain, errorBuilder: (_, _, _) => const SizedBox()),
      ),
    ]);
  }
}

class Price extends StatelessWidget {
  final double value;
  final bool from;
  final double size;
  const Price(this.value, {super.key, this.from = false, this.size = 17});

  @override
  Widget build(BuildContext context) {
    final l = AppScope.of(context).lang;
    return Text.rich(TextSpan(children: [
      if (from) TextSpan(text: '${tr('from', l)} ', style: const TextStyle(fontSize: 11, color: Brand.muted)),
      TextSpan(text: money(value), style: TextStyle(fontWeight: FontWeight.w800, fontSize: size, color: Brand.brown)),
      TextSpan(text: ' ${tr('aed', l)}', style: const TextStyle(fontSize: 11, color: Brand.muted)),
    ]));
  }
}

class DishCard extends StatelessWidget {
  final MenuItem item;
  const DishCard(this.item, {super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    return Material(
      color: Brand.card,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18), side: const BorderSide(color: Brand.line)),
      child: InkWell(
        onTap: () => addToCart(context, item),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          AspectRatio(aspectRatio: 4 / 3, child: DishImage(item)),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(item.name.of(l), maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                Text(item.name.of(l == 'ar' ? 'en' : 'ar'), maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, color: Brand.muted)),
                if (onlyAtText(s, item) case final only?)
                  Text('📍 $only', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, color: Color(0xFF3C2E9B), fontWeight: FontWeight.w700)),
                const Spacer(),
                Row(children: [
                  Expanded(child: Price(item.displayPrice, from: item.hasOptions, size: 15)),
                  _AddButton(label: item.hasChoices ? tr('choose', l) : '+ ${tr('add', l)}', onTap: () => addToCart(context, item)),
                ]),
              ]),
            ),
          ),
        ]),
      ),
    );
  }
}

class _AddButton extends StatelessWidget {
  final String label;
  final VoidCallback onTap;
  const _AddButton({required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) => Material(
        color: Brand.gold,
        shape: const StadiumBorder(),
        child: InkWell(
          customBorder: const StadiumBorder(),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
            child: Text(label, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13)),
          ),
        ),
      );
}

/// Adds directly, or opens a sheet to pick the type / size and extra choices (rice, serving, extras).
Future<void> addToCart(BuildContext context, MenuItem item) async {
  final s = AppScope.read(context);
  if (!item.hasChoices) {
    s.add(item);
    _toast(context, tr('added', s.lang));
    return;
  }
  final picked = await showModalBottomSheet<(ItemOption?, List<Addon>)>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Brand.cream,
    constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * .9),
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    builder: (_) => _OptionSheet(item: item, lang: s.lang),
  );
  if (picked != null && context.mounted) {
    s.add(item, picked.$1, picked.$2);
    _toast(context, tr('added', s.lang));
  }
}

void _toast(BuildContext context, String msg) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(msg), duration: const Duration(milliseconds: 1200)));
}

class _OptionSheet extends StatefulWidget {
  final MenuItem item;
  final String lang;
  const _OptionSheet({required this.item, required this.lang});

  @override
  State<_OptionSheet> createState() => _OptionSheetState();
}

class _OptionSheetState extends State<_OptionSheet> {
  late ItemOption? selected = widget.item.options.isEmpty ? null : widget.item.options.first;
  final Map<int, Set<int>> picks = {}; // group id -> addon ids
  int? missing; // group id flagged when the customer skipped a required choice

  List<Addon> get chosen => [
        for (final g in widget.item.addonGroups)
          for (final a in g.addons)
            if (picks[g.id]?.contains(a.id) ?? false) a,
      ];

  double get total => (selected?.price ?? widget.item.price ?? 0) + chosen.fold(0.0, (t, a) => t + a.price);

  void toggle(AddonGroup g, Addon a) => setState(() {
        final set = picks.putIfAbsent(g.id, () => <int>{});
        if (g.multiple) {
          set.contains(a.id) ? set.remove(a.id) : set.add(a.id);
        } else if (set.contains(a.id)) {
          if (!g.required) set.clear(); // an optional single choice can be cleared
        } else {
          set
            ..clear()
            ..add(a.id);
        }
        if (missing == g.id && set.isNotEmpty) missing = null;
      });

  void confirm() {
    for (final g in widget.item.addonGroups) {
      if (g.required && (picks[g.id]?.isEmpty ?? true)) {
        setState(() => missing = g.id);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('${tr('pick_required', widget.lang)}: ${g.name.of(widget.lang)}')));
        return;
      }
    }
    Navigator.pop(context, (selected, chosen));
  }

  Widget _head(String title, bool required, {bool flagged = false}) => Padding(
        padding: const EdgeInsets.only(top: 14, bottom: 8),
        child: Row(children: [
          Expanded(child: Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: Brand.brown))),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
            decoration: BoxDecoration(
              color: flagged ? const Color(0xFFB23A2A) : (required ? Brand.brown : Brand.sand),
              borderRadius: BorderRadius.circular(99),
            ),
            child: Text(tr(required ? 'required' : 'optional', widget.lang),
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: required || flagged ? Colors.white : Brand.muted)),
          ),
        ]),
      );

  Widget _choice({required bool on, required bool square, required String label, required double price, required bool plus, required VoidCallback onTap}) =>
      Padding(
        padding: const EdgeInsets.only(bottom: 8),
        child: Material(
          color: on ? const Color(0xFFFFF8EA) : Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: BorderSide(color: on ? Brand.gold : Brand.line, width: 1.5),
          ),
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: onTap,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
              child: Row(children: [
                Icon(
                  square ? (on ? Icons.check_box : Icons.check_box_outline_blank) : (on ? Icons.radio_button_checked : Icons.radio_button_off),
                  color: Brand.brown,
                ),
                const SizedBox(width: 10),
                Expanded(child: Text(label, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15))),
                if (!plus) Price(price) else if (price > 0) Text('+${money(price)} ${tr('aed', widget.lang)}', style: const TextStyle(fontWeight: FontWeight.w700, color: Brand.brown)),
              ]),
            ),
          ),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final l = widget.lang;
    final item = widget.item;
    return SafeArea(
      child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Flexible(
          child: SingleChildScrollView(
            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              ClipRRect(borderRadius: const BorderRadius.vertical(top: Radius.circular(24)), child: SizedBox(height: 170, child: DishImage(item, height: 170))),
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
                child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                  Text(item.name.of(l), style: Theme.of(context).textTheme.headlineMedium),
                  if (!item.description.isEmpty)
                    Padding(padding: const EdgeInsets.only(top: 4), child: Text(item.description.of(l), style: const TextStyle(color: Brand.muted))),
                  if (item.options.isNotEmpty) ...[
                    _head(tr('choose_one', l), true),
                    for (final o in item.options)
                      _choice(on: o == selected, square: false, label: o.name.of(l), price: o.price, plus: false, onTap: () => setState(() => selected = o)),
                  ],
                  for (final g in item.addonGroups) ...[
                    _head(g.name.of(l), g.required, flagged: missing == g.id),
                    for (final a in g.addons)
                      _choice(
                        on: picks[g.id]?.contains(a.id) ?? false,
                        square: g.multiple,
                        label: a.name.of(l),
                        price: a.price,
                        plus: true,
                        onTap: () => toggle(g, a),
                      ),
                  ],
                ]),
              ),
            ]),
          ),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 16),
          child: FilledButton(onPressed: confirm, child: Text('+ ${tr('add', l)} · ${money(total)} ${tr('aed', l)}')),
        ),
      ]),
    );
  }
}

class SectionTitle extends StatelessWidget {
  final String text;
  final Widget? trailing;
  const SectionTitle(this.text, {super.key, this.trailing});

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.fromLTRB(16, 24, 16, 12),
        child: Row(children: [
          Expanded(child: Text(text, style: Theme.of(context).textTheme.headlineMedium)),
          if (trailing != null) trailing!,
        ]),
      );
}

/// Chips to pick the branch; dishes the picked branch does not make are hidden.
class BranchPicker extends StatelessWidget {
  const BranchPicker({super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    if (s.branches.length < 2) return const SizedBox.shrink();
    Widget chip(String label, int? id) => Padding(
          padding: const EdgeInsetsDirectional.only(end: 8),
          child: ChoiceChip(
            label: Text(label),
            selected: s.branch == id,
            onSelected: (_) => s.setBranch(id),
            selectedColor: Brand.brown,
            labelStyle: TextStyle(color: s.branch == id ? Colors.white : Brand.brown, fontWeight: FontWeight.w700),
            showCheckmark: false,
            side: const BorderSide(color: Brand.line),
          ),
        );
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
      child: Row(children: [
        Text('${tr('pick_branch', l)}:  ', style: const TextStyle(fontWeight: FontWeight.w700, color: Brand.brown)),
        chip(tr('all', l), null),
        for (final b in s.branches) chip(b.name.of(l), b.id),
      ]),
    );
  }
}

/// "Only at Al Danah" under a dish that one branch makes.
String? onlyAtText(AppState s, MenuItem item) {
  if (item.branchIds.isEmpty) return null;
  final names = s.branches.where((b) => item.branchIds.contains(b.id)).map((b) => b.name.of(s.lang)).join(' / ');
  return names.isEmpty ? null : tr('only_at', s.lang).replaceAll(':b', names);
}
