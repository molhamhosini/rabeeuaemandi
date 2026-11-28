import 'dart:convert';

import 'package:material_ui/material_ui.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api.dart';
import 'models.dart';

class CartLine {
  final MenuItem item;
  final ItemOption? option;
  final List<Addon> addons;
  int qty;
  CartLine(this.item, this.option, this.qty, [this.addons = const []]);

  double get unitPrice => (option?.price ?? item.price ?? 0) + addons.fold(0.0, (a, x) => a + x.price);
  double get total => unitPrice * qty;
  String get key => lineKey(item, option, addons);

  static String lineKey(MenuItem item, ItemOption? option, List<Addon> addons) =>
      '${item.id}:${option?.id ?? ''}:${(addons.map((a) => a.id).toList()..sort()).join(',')}';
}

/// Holds language, menu data and the cart. Widgets rebuild via ListenableBuilder.
class AppState extends ChangeNotifier {
  final Api api;
  AppState(this.api);

  String lang = 'ar';
  bool loading = true;
  String? error;
  AppConfig config = AppConfig(false, '');
  List<Category> categories = [];
  List<Branch> branches = [];
  final List<CartLine> cart = [];
  final List<String> myOrders = [];
  /// The signed-in customer, or null for a guest. The token is kept in the phone's storage.
  Customer? customer;
  bool get signedIn => customer != null;
  /// The branch the customer picked; dishes another branch does not make are hidden.
  int? branch;

  /// The dishes of a section that the picked branch makes.
  List<MenuItem> itemsOf(Category c) => c.items.where((i) => i.availableAt(branch)).toList();

  Future<void> setBranch(int? id) async {
    branch = id;
    final p = await SharedPreferences.getInstance();
    id == null ? await p.remove('branch') : await p.setInt('branch', id);
    notifyListeners();
  }

  List<Area> get areas => branches.expand((b) => b.areas).toList();

  /// The branch closest to a map pin (straight-line distance), or null when branches have no location.
  int? nearestBranch((double, double) p) {
    int? best;
    var bestD = double.infinity;
    for (final b in branches) {
      final loc = b.location;
      if (loc == null) continue;
      final dLat = loc.$1 - p.$1, dLng = (loc.$2 - p.$2) * 0.91; // cos(24°) for longitude
      final d = dLat * dLat + dLng * dLng;
      if (d < bestD) {
        bestD = d;
        best = b.id;
      }
    }
    return best;
  }

  List<MenuItem> get featured => categories.expand((c) => c.items).where((i) => i.featured && i.availableAt(branch)).toList();
  int get count => cart.fold(0, (a, l) => a + l.qty);
  double get subtotal => cart.fold(0.0, (a, l) => a + l.total);

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    lang = prefs.getString('lang') ?? 'ar';
    branch = prefs.getInt('branch');
    myOrders.addAll(prefs.getStringList('orders') ?? []);
    api.token = prefs.getString('token');
    final saved = prefs.getString('customer');
    if (api.token != null && saved != null) customer = Customer.fromJson(jsonDecode(saved));
    await load();
    if (api.token != null) refreshMe();
  }

  Future<void> load() async {
    loading = true;
    error = null;
    notifyListeners();
    try {
      final r = await Future.wait([api.config(lang), api.menu(lang), api.branches(lang)]);
      config = r[0] as AppConfig;
      categories = r[1] as List<Category>;
      branches = r[2] as List<Branch>;
    } catch (e) {
      error = '$e';
    }
    loading = false;
    notifyListeners();
  }

  /// Signed in after the code was right: keep the token so the customer stays signed in.
  Future<void> signedInAs(String token, Customer c) async {
    api.token = token;
    final p = await SharedPreferences.getInstance();
    await p.setString('token', token);
    await _setCustomer(c);
    if (branch == null && c.branchId != null) await setBranch(c.branchId);
  }

  Future<void> _setCustomer(Customer c) async {
    customer = c;
    (await SharedPreferences.getInstance()).setString('customer', jsonEncode(c.toJson()));
    notifyListeners();
  }

  /// Fetch the latest name/address; a refused token means the sign-in ended.
  Future<void> refreshMe() async {
    try {
      await _setCustomer(await api.me(lang));
    } on UnauthorizedException {
      await _forget();
    } catch (_) {
      // offline: keep the saved copy
    }
  }

  Future<void> updateProfile({String? name, String? address}) async {
    await _setCustomer(await api.updateMe({'name': ?name, 'address': ?address}, lang));
  }

  Future<void> signOut() async {
    try {
      await api.logout(lang);
    } catch (_) {}
    await _forget();
  }

  Future<void> deleteAccount() async {
    await api.deleteAccount(lang);
    await _forget();
  }

  Future<void> _forget() async {
    api.token = null;
    customer = null;
    final p = await SharedPreferences.getInstance();
    await p.remove('token');
    await p.remove('customer');
    notifyListeners();
  }

  Future<void> toggleLang() async {
    lang = lang == 'ar' ? 'en' : 'ar';
    (await SharedPreferences.getInstance()).setString('lang', lang);
    notifyListeners();
  }

  void add(MenuItem item, [ItemOption? option, List<Addon> addons = const []]) {
    final key = CartLine.lineKey(item, option, addons);
    final ex = cart.where((l) => l.key == key);
    if (ex.isNotEmpty) {
      ex.first.qty++;
    } else {
      cart.add(CartLine(item, option, 1, addons));
    }
    notifyListeners();
  }

  /// The most notice any dish in the cart needs, in hours.
  int get leadHours => cart.fold(0, (m, l) => l.item.leadHours > m ? l.item.leadHours : m);

  void changeQty(CartLine line, int delta) {
    line.qty += delta;
    if (line.qty <= 0) cart.remove(line);
    notifyListeners();
  }

  void clearCart() {
    cart.clear();
    notifyListeners();
  }

  Future<void> rememberOrder(String code) async {
    myOrders.insert(0, code);
    (await SharedPreferences.getInstance()).setStringList('orders', myOrders.take(20).toList());
  }

  Map<String, dynamic> orderBody({
    required int branchId,
    required String name,
    required String phone,
    required String fulfillment,
    required String address,
    required String notes,
    required String payment,
    String? scheduledAt,
    (double, double)? location,
    int? areaId,
  }) =>
      {
        'branch_id': branchId,
        'customer_name': name,
        'customer_phone': phone,
        'fulfillment': fulfillment,
        'address': address,
        'notes': notes,
        'payment_method': payment,
        if (scheduledAt != null) 'scheduled_at': scheduledAt,
        if (location != null && fulfillment == 'delivery') ...{'lat': location.$1, 'lng': location.$2},
        if (areaId != null && fulfillment == 'delivery') 'area_id': areaId,
        'items': cart.map((l) => {'item_id': l.item.id, 'option_id': l.option?.id, 'addon_ids': l.addons.map((a) => a.id).toList(), 'quantity': l.qty}).toList(),
      };

  String encodeCart() => jsonEncode(cart.map((l) => l.key).toList());
}

/// The app's state comes from `provider`: `AppScope.of(context)` rebuilds the widget when the
/// state changes (context.watch), `AppScope.read(context)` reads it once (context.read).
abstract final class AppScope {
  static AppState of(BuildContext context) => context.watch<AppState>();
  static AppState read(BuildContext context) => context.read<AppState>();
}
