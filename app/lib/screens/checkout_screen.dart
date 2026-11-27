import 'package:material_ui/material_ui.dart';
import 'package:url_launcher/url_launcher.dart';

import '../app_state.dart';
import '../strings.dart';
import '../theme.dart';
import 'order_screen.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _form = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _address = TextEditingController();
  final _notes = TextEditingController();
  int? _branch;
  String _fulfillment = 'delivery';
  String? _payment;
  bool _later = false;
  /// Wanted time as Abu Dhabi wall-clock time (UTC+4, no daylight saving).
  DateTime? _at;
  bool _busy = false;
  String? _error;

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    _branch ??= s.branches.isNotEmpty ? s.branches.first.id : null;
    _payment ??= s.config.cardPayment ? 'card' : 'whatsapp';
    String? req(String? v) => (v == null || v.trim().isEmpty) ? tr('required', l) : null;

    return Scaffold(
      appBar: AppBar(title: Text(tr('checkout', l))),
      body: Form(
        key: _form,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          _Card(title: tr('branch', l), children: [
            for (final b in s.branches)
              _Choice(
                selected: _branch == b.id,
                title: b.name.of(l),
                subtitle: b.address.of(l),
                onTap: () => setState(() => _branch = b.id),
              ),
          ]),
          _Card(title: tr('fulfillment', l), children: [
            Row(children: [
              Expanded(child: _Choice(selected: _fulfillment == 'delivery', title: tr('deliver', l), subtitle: tr('free_delivery', l), onTap: () => setState(() => _fulfillment = 'delivery'))),
              const SizedBox(width: 10),
              Expanded(child: _Choice(selected: _fulfillment == 'pickup', title: tr('pickup', l), subtitle: '', onTap: () => setState(() => _fulfillment = 'pickup'))),
            ]),
            const SizedBox(height: 6),
            TextFormField(controller: _name, decoration: InputDecoration(labelText: tr('name', l)), validator: req, textInputAction: TextInputAction.next),
            const SizedBox(height: 12),
            TextFormField(
              controller: _phone,
              decoration: InputDecoration(labelText: tr('phone', l), hintText: '05X XXX XXXX'),
              keyboardType: TextInputType.phone,
              textDirection: TextDirection.ltr,
              validator: (v) => req(v) ?? (RegExp(r'^[0-9+\s-]{7,20}$').hasMatch(v!.trim()) ? null : tr('required', l)),
            ),
            if (_fulfillment == 'delivery') ...[
              const SizedBox(height: 12),
              TextFormField(controller: _address, decoration: InputDecoration(labelText: tr('address', l)), validator: req, maxLines: 2),
            ],
            const SizedBox(height: 12),
            TextFormField(controller: _notes, decoration: InputDecoration(labelText: tr('notes', l)), maxLines: 2),
          ]),
          _whenCard(s, l),
          _Card(title: tr('payment', l), children: [
            _Choice(
              selected: _payment == 'card',
              enabled: s.config.cardPayment,
              title: tr('pay_card', l),
              subtitle: s.config.cardPayment ? tr('pay_card_sub', l) : tr('card_off', l),
              onTap: () => setState(() => _payment = 'card'),
            ),
            _Choice(selected: _payment == 'whatsapp', title: tr('pay_wa', l), subtitle: tr('pay_wa_sub', l), onTap: () => setState(() => _payment = 'whatsapp')),
          ]),
          if (_error != null) Padding(padding: const EdgeInsets.only(bottom: 10), child: Text(_error!, style: const TextStyle(color: Brand.red))),
        ]),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: FilledButton(
            style: _payment == 'whatsapp' ? FilledButton.styleFrom(backgroundColor: Brand.whatsapp) : null,
            onPressed: _busy || s.cart.isEmpty ? null : () => _submit(s),
            child: _busy
                ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                : Text('${_payment == 'card' ? tr('submit_card', l) : tr('submit_wa', l)} · ${money(s.subtotal)} ${tr('aed', l)}'),
          ),
        ),
      ),
    );
  }

  static DateTime _abuDhabiNow() {
    final u = DateTime.now().toUtc().add(const Duration(hours: 4));
    return DateTime(u.year, u.month, u.day, u.hour, u.minute);
  }

  /// Earliest allowed time, rounded up to the next quarter hour.
  DateTime _earliest(int lead) {
    final t = _abuDhabiNow().add(Duration(hours: lead));
    final extra = (15 - t.minute % 15) % 15;
    return t.add(Duration(minutes: extra));
  }

  String _fmt(DateTime t) {
    String two(int v) => v.toString().padLeft(2, '0');
    return '${t.year}-${two(t.month)}-${two(t.day)}T${two(t.hour)}:${two(t.minute)}';
  }

  Widget _whenCard(AppState s, String l) {
    final lead = s.leadHours;
    final later = _later || lead > 0;
    final earliest = _earliest(lead);
    if (later && (_at == null || _at!.isBefore(earliest))) _at = earliest;
    final h = TimeOfDay.fromDateTime(_at ?? earliest);
    return _Card(title: tr('when', l), children: [
      _Choice(selected: !later, enabled: lead == 0, title: tr('asap', l), subtitle: tr('asap_sub', l), onTap: () => setState(() => _later = false)),
      _Choice(selected: later, title: tr('later', l), subtitle: tr('pick_time', l), onTap: () => setState(() => _later = true)),
      if (lead > 0)
        Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: const Color(0xFFFFF3DC), borderRadius: BorderRadius.circular(12), border: Border.all(color: Brand.gold)),
          child: Text(tr('lead_note', l).replaceAll(':h', '$lead'), style: const TextStyle(fontWeight: FontWeight.w700, color: Brand.brown)),
        ),
      if (later)
        OutlinedButton.icon(
          icon: const Icon(Icons.event_rounded),
          label: Text('${_at!.day}/${_at!.month}/${_at!.year}  ·  ${MaterialLocalizations.of(context).formatTimeOfDay(h)}'),
          onPressed: () async {
            final day = await showDatePicker(
              context: context,
              initialDate: _at!,
              firstDate: DateTime(earliest.year, earliest.month, earliest.day),
              lastDate: earliest.add(const Duration(days: 30)),
            );
            if (day == null || !mounted) return;
            final time = await showTimePicker(context: context, initialTime: h);
            if (time == null || !mounted) return;
            final picked = DateTime(day.year, day.month, day.day, time.hour, time.minute);
            setState(() {
              if (picked.isBefore(earliest)) {
                _at = earliest;
                _error = tr('too_soon', l).replaceAll(':h', '$lead');
              } else {
                _at = picked;
                _error = null;
              }
            });
          },
        ),
    ]);
  }

  Future<void> _submit(AppState s) async {
    if (!_form.currentState!.validate() || _branch == null) return;
    final later = _later || s.leadHours > 0;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final order = await s.api.placeOrder(
        s.orderBody(
          branchId: _branch!,
          name: _name.text.trim(),
          phone: _phone.text.trim(),
          fulfillment: _fulfillment,
          address: _address.text.trim(),
          notes: _notes.text.trim(),
          payment: _payment!,
          scheduledAt: later && _at != null ? _fmt(_at!) : null,
        ),
        s.lang,
      );
      await s.rememberOrder(order.code);
      s.clearCart();
      // WhatsApp opens the chat with the order typed out; card opens Stripe's secure payment page.
      await launchUrl(Uri.parse(order.redirect), mode: LaunchMode.externalApplication);
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => OrderScreen(code: order.code)));
    } catch (e) {
      setState(() => _error = '$e');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}

class _Card extends StatelessWidget {
  final String title;
  final List<Widget> children;
  const _Card({required this.title, required this.children});

  @override
  Widget build(BuildContext context) => Container(
        margin: const EdgeInsets.only(bottom: 14),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: Brand.card, borderRadius: BorderRadius.circular(18), border: Border.all(color: Brand.line)),
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Text(title, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 10),
          ...children,
        ]),
      );
}

class _Choice extends StatelessWidget {
  final bool selected;
  final bool enabled;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  const _Choice({required this.selected, required this.title, required this.subtitle, required this.onTap, this.enabled = true});

  @override
  Widget build(BuildContext context) => Opacity(
        opacity: enabled ? 1 : .5,
        child: Padding(
          padding: const EdgeInsets.only(bottom: 8),
          child: Material(
            color: selected ? const Color(0xFFFFF8EA) : Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: BorderSide(color: selected ? Brand.brown : Brand.line, width: 1.5)),
            child: InkWell(
              borderRadius: BorderRadius.circular(14),
              onTap: enabled ? onTap : null,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Row(children: [
                  Icon(selected ? Icons.radio_button_checked : Icons.radio_button_off, color: Brand.brown, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
                      if (subtitle.isNotEmpty) Text(subtitle, style: const TextStyle(color: Brand.muted, fontSize: 12)),
                    ]),
                  ),
                ]),
              ),
            ),
          ),
        ),
      );
}
