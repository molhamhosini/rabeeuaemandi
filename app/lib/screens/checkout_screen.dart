import 'package:material_ui/material_ui.dart';
import 'package:url_launcher/url_launcher.dart';

import '../app_state.dart';
import '../models.dart';
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
    _branch ??= s.branches.any((b) => b.id == s.branch) ? s.branch : (s.branches.isNotEmpty ? s.branches.first.id : null);
    final payments = s.config.payments;
    if (_payment == null || !payments.contains(_payment)) _payment = payments.isNotEmpty ? payments.first : null;
    // dishes in the cart that the chosen branch does not make
    final missing = s.cart.where((c) => !c.item.availableAt(_branch)).map((c) => c.item.name.of(l)).toSet().toList();
    final blocked = !s.config.takingOrders || missing.isNotEmpty || _payment == null;
    String? req(String? v) => (v == null || v.trim().isEmpty) ? tr('required', l) : null;

    return Scaffold(
      appBar: AppBar(title: Text(tr('checkout', l))),
      body: Form(
        key: _form,
        child: ListView(padding: const EdgeInsets.all(16), children: [
          if (!s.config.takingOrders)
            Container(
              margin: const EdgeInsets.only(bottom: 14),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(color: const Color(0xFFFBE3E0), borderRadius: BorderRadius.circular(14)),
              child: Text(tr('paused', l), style: const TextStyle(color: Brand.red, fontWeight: FontWeight.w700)),
            ),
          _Card(title: tr('branch', l), children: [
            for (final b in s.branches)
              _Choice(
                selected: _branch == b.id,
                title: b.name.of(l),
                subtitle: b.address.of(l),
                onTap: () {
                  setState(() => _branch = b.id);
                  s.setBranch(b.id);
                },
              ),
            if (missing.isNotEmpty)
              Text('${missing.join('، ')}: ${tr('not_here', l)}', style: const TextStyle(color: Brand.red, fontWeight: FontWeight.w700)),
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
            for (final m in payments)
              _Choice(
                selected: _payment == m,
                title: tr(m == 'whatsapp' ? 'pay_wa' : 'pay_$m', l),
                subtitle: tr(m == 'whatsapp' ? 'pay_wa_sub' : 'pay_${m}_sub', l),
                onTap: () => setState(() => _payment = m),
              ),
          ]),
          if (_error != null) Padding(padding: const EdgeInsets.only(bottom: 10), child: Text(_error!, style: const TextStyle(color: Brand.red))),
        ]),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: FilledButton(
            style: _payment == 'whatsapp' ? FilledButton.styleFrom(backgroundColor: Brand.whatsapp) : null,
            onPressed: _busy || s.cart.isEmpty || blocked ? null : () => _submit(s),
            child: _busy
                ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                : Text('${tr(switch (_payment) { 'card' => 'submit_card', 'cash' => 'submit_cash', _ => 'submit_wa' }, l)} · ${money(s.subtotal)} ${tr('aed', l)}'),
          ),
        ),
      ),
    );
  }

  static DateTime _abuDhabiNow() {
    final u = DateTime.now().toUtc().add(const Duration(hours: 4));
    return DateTime(u.year, u.month, u.day, u.hour, u.minute);
  }

  static int _mins(String hm) {
    final p = hm.split(':');
    return int.parse(p[0]) * 60 + int.parse(p[1]);
  }

  /// The choices: each day with its times. Ordinary meals: today only, at least [AppConfig.meal]
  /// minutes ahead. Banquets and the cooking service: [lead] hours ahead, on any of the next days.
  List<(DateTime, List<DateTime>)> _days(AppConfig c, int lead) {
    final now = _abuDhabiNow();
    final earliest = now.add(Duration(minutes: (lead > 0 ? lead * 60 : c.meal) - 5));
    final out = <(DateTime, List<DateTime>)>[];
    for (var i = 0; i <= (lead > 0 ? c.days : 0); i++) {
      final d = DateTime(now.year, now.month, now.day + i);
      final slots = <DateTime>[
        for (var m = _mins(c.open); m <= _mins(c.last); m += c.step)
          if (!d.add(Duration(minutes: m)).isBefore(earliest)) d.add(Duration(minutes: m)),
      ];
      if (slots.isNotEmpty) out.add((d, slots));
    }
    return out;
  }

  bool _openNow(AppConfig c) {
    final n = _abuDhabiNow();
    final m = n.hour * 60 + n.minute;
    return m >= _mins(c.open) && m <= _mins(c.last);
  }

  String _fmt(DateTime t) {
    String two(int v) => v.toString().padLeft(2, '0');
    return '${t.year}-${two(t.month)}-${two(t.day)}T${two(t.hour)}:${two(t.minute)}';
  }

  String _time(DateTime t) => MaterialLocalizations.of(context).formatTimeOfDay(TimeOfDay.fromDateTime(t));

  Widget _whenCard(AppState s, String l) {
    final c = s.config;
    final lead = s.leadHours;
    final days = _days(c, lead);
    final noAsap = lead > 0 || !_openNow(c);
    final later = _later || noAsap;
    var dayIdx = 0;
    if (_at != null) {
      final i = days.indexWhere((d) => d.$2.contains(_at));
      if (i >= 0) {
        dayIdx = i;
      } else {
        _at = null;
      }
    }
    if (later && _at == null && days.isNotEmpty) _at = days.first.$2.first;
    const week = {'ar': ['الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت', 'الأحد'], 'en': ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']};
    final now = _abuDhabiNow();
    String dayName(DateTime d) {
      final diff = DateTime(d.year, d.month, d.day).difference(DateTime(now.year, now.month, now.day)).inDays;
      if (diff == 0) return tr('today', l);
      if (diff == 1) return tr('tomorrow', l);
      return '${week[l]![d.weekday - 1]} ${d.day}/${d.month}';
    }

    final noteText = days.isEmpty ? tr('closed_today', l) : (lead > 0 ? tr('lead_note', l).replaceAll(':h', '$lead') : null);
    return _Card(title: tr('when', l), children: [
      _Choice(selected: !later, enabled: !noAsap, title: tr('asap', l), subtitle: tr('asap_sub', l), onTap: () => setState(() => _later = false)),
      _Choice(selected: later, title: tr('later', l), subtitle: tr('pick_time', l), onTap: () => setState(() => _later = true)),
      Text(tr('hours_note', l).replaceAll(':o', _time(DateTime(2000, 1, 1, _mins(c.open) ~/ 60, _mins(c.open) % 60))).replaceAll(':l', _time(DateTime(2000, 1, 1, _mins(c.last) ~/ 60, _mins(c.last) % 60))),
          style: const TextStyle(color: Brand.muted, fontSize: 13)),
      const SizedBox(height: 8),
      if (noteText != null)
        Container(
          margin: const EdgeInsets.only(bottom: 8),
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: const Color(0xFFFFF3DC), borderRadius: BorderRadius.circular(12), border: Border.all(color: Brand.gold)),
          child: Text(noteText, style: const TextStyle(fontWeight: FontWeight.w700, color: Brand.brown)),
        ),
      if (later && days.isNotEmpty)
        Row(children: [
          if (lead > 0) ...[
            Expanded(
              child: DropdownButtonFormField<int>(
                initialValue: dayIdx,
                isExpanded: true,
                decoration: InputDecoration(labelText: tr('day', l)),
                items: [for (var i = 0; i < days.length; i++) DropdownMenuItem(value: i, child: Text(dayName(days[i].$1)))],
                onChanged: (i) => setState(() => _at = days[i!].$2.first),
              ),
            ),
            const SizedBox(width: 10),
          ],
          Expanded(
            child: DropdownButtonFormField<DateTime>(
              key: ValueKey(dayIdx),
              initialValue: _at,
              isExpanded: true,
              decoration: InputDecoration(labelText: tr('time', l)),
              items: [for (final t in days[dayIdx].$2) DropdownMenuItem(value: t, child: Text(_time(t)))],
              onChanged: (t) => setState(() => _at = t),
            ),
          ),
        ]),
    ]);
  }

  Future<void> _submit(AppState s) async {
    if (!_form.currentState!.validate() || _branch == null) return;
    final later = _later || s.leadHours > 0 || !_openNow(s.config);
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
      // WhatsApp opens the chat with the order typed out; card opens Stripe's secure payment page;
      // a cash order is already with the restaurant.
      if (order.redirect case final url?) await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
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
