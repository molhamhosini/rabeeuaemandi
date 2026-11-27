import 'package:material_ui/material_ui.dart';

import '../app_state.dart';
import '../strings.dart';
import '../theme.dart';
import 'checkout_screen.dart';
import 'widgets.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    return Scaffold(
      appBar: AppBar(title: Text(tr('cart', l))),
      body: s.cart.isEmpty
          ? Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                const Icon(Icons.shopping_bag_outlined, size: 56, color: Brand.gold),
                const SizedBox(height: 10),
                Text(tr('empty', l), style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 17)),
                Text(tr('empty_sub', l), style: const TextStyle(color: Brand.muted)),
              ]),
            )
          : ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: s.cart.length,
              separatorBuilder: (_, _) => const Divider(color: Brand.line, height: 20),
              itemBuilder: (_, i) {
                final line = s.cart[i];
                return Row(children: [
                  ClipRRect(borderRadius: BorderRadius.circular(12), child: SizedBox(width: 72, height: 58, child: DishImage(line.item))),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(line.item.name.of(l), style: const TextStyle(fontWeight: FontWeight.w700)),
                      if (line.option != null) Text(line.option!.name.of(l), style: const TextStyle(color: Brand.muted, fontSize: 13)),
                      if (line.addons.isNotEmpty)
                        Text(line.addons.map((a) => a.name.of(l)).join(l == 'en' ? ', ' : '، '),
                            style: const TextStyle(color: Brand.gold2, fontSize: 13, fontWeight: FontWeight.w600)),
                      Price(line.unitPrice, size: 14),
                    ]),
                  ),
                  _Qty(line: line),
                ]);
              },
            ),
      bottomNavigationBar: s.cart.isEmpty
          ? null
          : SafeArea(
              child: Container(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
                decoration: const BoxDecoration(color: Brand.card, border: Border(top: BorderSide(color: Brand.line))),
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  _SumRow(tr('subtotal', l), '${money(s.subtotal)} ${tr('aed', l)}'),
                  _SumRow(tr('delivery', l), tr('free', l)),
                  const SizedBox(height: 10),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton(
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const CheckoutScreen())),
                      child: Text('${tr('checkout', l)} · ${money(s.subtotal)} ${tr('aed', l)}'),
                    ),
                  ),
                ]),
              ),
            ),
    );
  }
}

class _SumRow extends StatelessWidget {
  final String a, b;
  const _SumRow(this.a, this.b);
  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(children: [Text(a, style: const TextStyle(color: Brand.muted)), const Spacer(), Text(b, style: const TextStyle(fontWeight: FontWeight.w700))]),
      );
}

class _Qty extends StatelessWidget {
  final CartLine line;
  const _Qty({required this.line});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.read(context);
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(999), border: Border.all(color: Brand.line)),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        IconButton(visualDensity: VisualDensity.compact, onPressed: () => s.changeQty(line, -1), icon: const Icon(Icons.remove, size: 18, color: Brand.brown)),
        Text('${line.qty}', style: const TextStyle(fontWeight: FontWeight.w800)),
        IconButton(visualDensity: VisualDensity.compact, onPressed: () => s.changeQty(line, 1), icon: const Icon(Icons.add, size: 18, color: Brand.brown)),
      ]),
    );
  }
}
