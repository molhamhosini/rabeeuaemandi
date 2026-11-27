import 'package:material_ui/material_ui.dart';

import '../app_state.dart';
import '../models.dart';
import '../strings.dart';
import '../theme.dart';

class OrderScreen extends StatefulWidget {
  final String code;
  const OrderScreen({super.key, required this.code});

  @override
  State<OrderScreen> createState() => _OrderScreenState();
}

class _OrderScreenState extends State<OrderScreen> {
  Map<String, dynamic>? order;
  String? error;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final s = AppScope.read(context);
    try {
      final o = await s.api.order(widget.code, s.lang);
      setState(() => order = o);
    } catch (e) {
      setState(() => error = '$e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = AppScope.of(context).lang;
    final o = order;
    return Scaffold(
      appBar: AppBar(title: Text('${tr('order_no', l)}${o?['number'] != null ? ' #${o!['number']}' : ''}')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(padding: const EdgeInsets.all(20), children: [
          const SizedBox(height: 10),
          const CircleAvatar(radius: 38, backgroundColor: Brand.sand, child: Icon(Icons.check_rounded, size: 40, color: Brand.gold2)),
          const SizedBox(height: 12),
          Text(tr('thanks', l), textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineMedium),
          const SizedBox(height: 16),
          if (o == null && error == null) const Center(child: CircularProgressIndicator(color: Brand.gold)),
          if (error != null) Text(error!, textAlign: TextAlign.center, style: const TextStyle(color: Brand.red)),
          if (o != null) ...[
            Wrap(alignment: WrapAlignment.center, spacing: 8, runSpacing: 8, children: [
              Chip(label: Text('${tr('status', l)}: ${tr('st_${o['status']}', l)}'), backgroundColor: Brand.sand, side: BorderSide.none),
              Chip(label: Text('${tr('payment', l)}: ${tr('pay_${o['payment_status']}', l)}'), backgroundColor: Brand.sand, side: BorderSide.none),
            ]),
            if (o['when'] != null) ...[
              const SizedBox(height: 10),
              Text('${tr('when', l)}: ${L10nText.fromJson(o['when']).of(l)}', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w700, color: Brand.brown)),
            ],
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Brand.card, borderRadius: BorderRadius.circular(18), border: Border.all(color: Brand.line)),
              child: Column(children: [
                for (final i in (o['items'] as List))
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4),
                    child: Row(children: [
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text('${i['quantity']} × ${L10nText.fromJson(i['name']).of(l)}'),
                          if (L10nText.fromJson(i['details']).of(l).isNotEmpty)
                            Text(L10nText.fromJson(i['details']).of(l), style: const TextStyle(color: Brand.gold2, fontSize: 13)),
                        ]),
                      ),
                      Text(money((i['line_total'] as num).toDouble())),
                    ]),
                  ),
                const Divider(color: Brand.line),
                Row(children: [
                  Text(tr('total', l), style: const TextStyle(fontWeight: FontWeight.w800)),
                  const Spacer(),
                  Text('${money((o['total'] as num).toDouble())} ${tr('aed', l)}', style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
                ]),
              ]),
            ),
          ],
          const SizedBox(height: 20),
          OutlinedButton(onPressed: _load, child: Text(tr('refresh', l))),
          TextButton(onPressed: () => Navigator.of(context).pop(), child: Text(tr('back_home', l))),
        ]),
      ),
    );
  }
}
