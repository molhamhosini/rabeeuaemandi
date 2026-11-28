import 'dart:convert';

import 'package:material_ui/material_ui.dart';
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

  List<MenuItem> get featured => categories.expand((c) => c.items).where((i) => i.featured && i.availableAt(branch)).toList();
  int get count => cart.fold(0, (a, l) => a + l.qty);
  double get subtotal => cart.fold(0.0, (a, l) => a + l.total);

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    lang = prefs.getString('lang') ?? 'ar';
    branch = prefs.getInt('branch');
    myOrders.addAll(prefs.getStringList('orders') ?? []);
    await load();
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
        'items': cart.map((l) => {'item_id': l.item.id, 'option_id': l.option?.id, 'addon_ids': l.addons.map((a) => a.id).toList(), 'quantity': l.qty}).toList(),
      };

  String encodeCart() => jsonEncode(cart.map((l) => l.key).toList());
}

class AppScope extends InheritedNotifier<AppState> {
  const AppScope({super.key, required AppState state, required super.child}) : super(notifier: state);

  static AppState of(BuildContext context) => context.dependOnInheritedWidgetOfExactType<AppScope>()!.notifier!;
  static AppState read(BuildContext context) => context.getInheritedWidgetOfExactType<AppScope>()!.notifier!;
}
