import 'package:material_ui/material_ui.dart';

import '../api.dart';
import '../app_state.dart';
import '../models.dart';
import '../strings.dart';
import '../theme.dart';
import 'auth_screen.dart';
import 'order_screen.dart';

/// The "Account" tab: sign in, name and saved address, past orders, sign out, delete account.
class AccountScreen extends StatefulWidget {
  const AccountScreen({super.key});

  @override
  State<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends State<AccountScreen> {
  List<Map<String, dynamic>>? _orders;
  String? _error;
  int? _loadedFor;

  Future<void> _load() async {
    final s = AppScope.read(context);
    if (!s.signedIn) return;
    try {
      final o = await s.api.myOrders(s.lang);
      if (mounted) setState(() => _orders = o);
    } on UnauthorizedException {
      await s.refreshMe();
    } catch (e) {
      if (mounted) setState(() => _error = '$e');
    }
  }

  Future<void> _edit(String field, String label, String? value) async {
    final s = AppScope.read(context);
    final c = TextEditingController(text: value);
    final v = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(label),
        content: TextField(controller: c, autofocus: true, maxLines: field == 'address' ? 3 : 1),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: Text(tr('cancel', s.lang))),
          FilledButton(onPressed: () => Navigator.pop(ctx, c.text.trim()), child: Text(tr('save', s.lang))),
        ],
      ),
    );
    if (v == null) return;
    try {
      await (field == 'name' ? s.updateProfile(name: v) : s.updateProfile(address: v));
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  Future<void> _delete() async {
    final s = AppScope.read(context);
    final l = s.lang;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(tr('delete_account', l)),
        content: Text(tr('delete_confirm', l)),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text(tr('cancel', l))),
          FilledButton(style: FilledButton.styleFrom(backgroundColor: Brand.red), onPressed: () => Navigator.pop(ctx, true), child: Text(tr('delete', l))),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await s.deleteAccount();
      setState(() => _orders = null);
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    final c = s.customer;
    // load the orders once per signed-in account
    if (c != null && _loadedFor != c.id) {
      _loadedFor = c.id;
      _orders = null;
      WidgetsBinding.instance.addPostFrameCallback((_) => _load());
    }
    if (c == null) _loadedFor = null;

    return Scaffold(
      appBar: AppBar(title: Text(tr('account', l)), actions: [
        TextButton(onPressed: s.toggleLang, child: Text(tr('language', l))),
      ]),
      body: c == null ? _guest(context, l) : RefreshIndicator(onRefresh: () async {
        await s.refreshMe();
        await _load();
      }, child: _signedIn(context, s, c, l)),
    );
  }

  Widget _guest(BuildContext context, String l) => Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const CircleAvatar(radius: 40, backgroundColor: Brand.sand, child: Icon(Icons.person_outline_rounded, size: 44, color: Brand.gold2)),
            const SizedBox(height: 14),
            Text(tr('sign_in', l), style: Theme.of(context).textTheme.headlineMedium),
            const SizedBox(height: 6),
            Text(tr('sign_in_sub', l), textAlign: TextAlign.center, style: const TextStyle(color: Brand.muted)),
            const SizedBox(height: 20),
            SizedBox(width: double.infinity, child: FilledButton(onPressed: () => AuthScreen.open(context), child: Text(tr('sign_in', l)))),
          ]),
        ),
      );

  Widget _signedIn(BuildContext context, AppState s, Customer c, String l) {
    final orders = _orders;
    return ListView(padding: const EdgeInsets.all(16), children: [
      Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(color: Brand.card, borderRadius: BorderRadius.circular(18), border: Border.all(color: Brand.line)),
        child: Column(children: [
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const CircleAvatar(backgroundColor: Brand.sand, child: Icon(Icons.person, color: Brand.gold2)),
            title: Text(c.name?.isNotEmpty == true ? c.name! : tr('your_name', l), style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
            subtitle: Text(c.phone, textDirection: TextDirection.ltr, textAlign: l == 'ar' ? TextAlign.right : TextAlign.left),
            trailing: IconButton(icon: const Icon(Icons.edit_outlined, color: Brand.gold2), onPressed: () => _edit('name', tr('name', l), c.name)),
          ),
          const Divider(color: Brand.line),
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: const Icon(Icons.location_on_outlined, color: Brand.gold2),
            title: Text(tr('saved_address', l), style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text(c.address?.isNotEmpty == true ? c.address! : '—'),
            trailing: IconButton(icon: const Icon(Icons.edit_outlined, color: Brand.gold2), onPressed: () => _edit('address', tr('saved_address', l), c.address)),
          ),
        ]),
      ),
      const SizedBox(height: 18),
      Text(tr('my_orders', l), style: Theme.of(context).textTheme.titleLarge),
      const SizedBox(height: 8),
      if (orders == null && _error == null) const Padding(padding: EdgeInsets.all(20), child: Center(child: CircularProgressIndicator(color: Brand.gold))),
      if (_error != null && orders == null) Text(_error!, style: const TextStyle(color: Brand.red)),
      if (orders != null && orders.isEmpty) Padding(padding: const EdgeInsets.all(16), child: Text(tr('no_orders', l), textAlign: TextAlign.center, style: const TextStyle(color: Brand.muted))),
      for (final o in orders ?? const <Map<String, dynamic>>[])
        Card(
          color: Colors.white,
          elevation: 0,
          margin: const EdgeInsets.only(bottom: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14), side: const BorderSide(color: Brand.line)),
          child: ListTile(
            onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => OrderScreen(code: o['code']))),
            title: Text('#${o['number']} · ${L10nText.fromJson(o['branch']['name']).of(l)}', style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text('${tr('st_${o['status']}', l)} · ${_date(o['created_at'])}'),
            trailing: Text('${money((o['total'] as num).toDouble())} ${tr('aed', l)}', style: const TextStyle(fontWeight: FontWeight.w800, color: Brand.brown)),
          ),
        ),
      const SizedBox(height: 18),
      OutlinedButton.icon(
        onPressed: () async {
          await s.signOut();
          setState(() => _orders = null);
        },
        icon: const Icon(Icons.logout),
        label: Text(tr('sign_out', l)),
        style: OutlinedButton.styleFrom(minimumSize: const Size(0, 48), shape: const StadiumBorder(), foregroundColor: Brand.brown),
      ),
      TextButton(onPressed: _delete, style: TextButton.styleFrom(foregroundColor: Brand.red), child: Text(tr('delete_account', l))),
    ]);
  }

  static String _date(String? iso) {
    if (iso == null) return '';
    final d = DateTime.parse(iso).toUtc().add(const Duration(hours: 4));
    return '${d.day}/${d.month}/${d.year}';
  }
}
