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
                const Spacer(),
                Row(children: [
                  Expanded(child: Price(item.displayPrice, from: item.hasOptions, size: 15)),
                  _AddButton(label: item.hasOptions ? tr('choose', l) : '+ ${tr('add', l)}', onTap: () => addToCart(context, item)),
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

/// Adds directly, or opens a sheet to pick the lamb type / harees size first.
Future<void> addToCart(BuildContext context, MenuItem item) async {
  final s = AppScope.read(context);
  if (!item.hasOptions) {
    s.add(item);
    _toast(context, tr('added', s.lang));
    return;
  }
  final picked = await showModalBottomSheet<ItemOption>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Brand.cream,
    shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
    builder: (_) => _OptionSheet(item: item, lang: s.lang),
  );
  if (picked != null && context.mounted) {
    s.add(item, picked);
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
  late ItemOption selected = widget.item.options.first;

  @override
  Widget build(BuildContext context) {
    final l = widget.lang;
    final item = widget.item;
    return SafeArea(
      child: SingleChildScrollView(
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          ClipRRect(borderRadius: const BorderRadius.vertical(top: Radius.circular(24)), child: SizedBox(height: 200, child: DishImage(item, height: 200))),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              Text(item.name.of(l), style: Theme.of(context).textTheme.headlineMedium),
              if (!item.description.isEmpty)
                Padding(padding: const EdgeInsets.only(top: 4, bottom: 6), child: Text(item.description.of(l), style: const TextStyle(color: Brand.muted))),
              const SizedBox(height: 10),
              for (final o in item.options)
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Material(
                    color: o == selected ? const Color(0xFFFFF8EA) : Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                      side: BorderSide(color: o == selected ? Brand.brown : Brand.line, width: 1.5),
                    ),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(14),
                      onTap: () => setState(() => selected = o),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        child: Row(children: [
                          Icon(o == selected ? Icons.radio_button_checked : Icons.radio_button_off, color: Brand.brown),
                          const SizedBox(width: 10),
                          Expanded(child: Text(o.name.of(l), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16))),
                          Price(o.price),
                        ]),
                      ),
                    ),
                  ),
                ),
              const SizedBox(height: 6),
              FilledButton(onPressed: () => Navigator.pop(context, selected), child: Text('+ ${tr('add', l)}')),
            ]),
          ),
        ]),
      ),
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
