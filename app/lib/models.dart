/// Bilingual text as returned by the API: {"ar": "...", "en": "..."}.
class L10nText {
  final String ar;
  final String en;
  const L10nText(this.ar, this.en);

  factory L10nText.fromJson(dynamic j) {
    if (j is Map) return L10nText('${j['ar'] ?? ''}', '${j['en'] ?? j['ar'] ?? ''}');
    return const L10nText('', '');
  }

  String of(String lang) => lang == 'en' ? (en.isNotEmpty ? en : ar) : (ar.isNotEmpty ? ar : en);
  bool get isEmpty => ar.isEmpty && en.isEmpty;
}

class ItemOption {
  final int id;
  final L10nText name;
  final double price;
  ItemOption(this.id, this.name, this.price);

  factory ItemOption.fromJson(Map<String, dynamic> j) =>
      ItemOption(j['id'] as int, L10nText.fromJson(j['name']), (j['price'] as num).toDouble());
}

/// One extra choice on a dish (a rice type, a serving style, an extra like chutney).
class Addon {
  final int id;
  final L10nText name;
  final double price; // added to the dish price
  Addon(this.id, this.name, this.price);

  factory Addon.fromJson(Map<String, dynamic> j) =>
      Addon(j['id'] as int, L10nText.fromJson(j['name']), (j['price'] as num).toDouble());
}

class AddonGroup {
  final int id;
  final L10nText name;
  final bool multiple;
  final bool required;
  final List<Addon> addons;
  AddonGroup(this.id, this.name, this.multiple, this.required, this.addons);

  factory AddonGroup.fromJson(Map<String, dynamic> j) => AddonGroup(
        j['id'] as int,
        L10nText.fromJson(j['name']),
        j['multiple'] == true,
        j['required'] == true,
        ((j['addons'] as List?) ?? []).map((a) => Addon.fromJson(a)).toList(),
      );
}

class MenuItem {
  final int id;
  final int categoryId;
  final L10nText name;
  final L10nText description;
  final double? price;
  final String? image;
  final bool featured;
  final List<ItemOption> options;
  final List<AddonGroup> addonGroups;
  /// Hours of notice the dish needs (whole lambs: 3); 0 = can be made right away.
  final int leadHours;
  /// Branches that make this dish; empty = every branch.
  final List<int> branchIds;

  MenuItem({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.description,
    required this.price,
    required this.image,
    required this.featured,
    required this.options,
    this.addonGroups = const [],
    this.leadHours = 0,
    this.branchIds = const [],
  });

  bool availableAt(int? branch) => branch == null || branchIds.isEmpty || branchIds.contains(branch);

  bool get hasOptions => options.isNotEmpty;
  bool get hasChoices => options.isNotEmpty || addonGroups.isNotEmpty;
  bool get isPhoto => (image?.contains('/photos/') ?? false) || (image?.contains('/menu/') ?? false);

  double get displayPrice {
    if (price != null) return price!;
    if (options.isEmpty) return 0;
    return options.map((o) => o.price).reduce((a, b) => a < b ? a : b);
  }

  factory MenuItem.fromJson(Map<String, dynamic> j) => MenuItem(
        id: j['id'] as int,
        categoryId: j['category_id'] as int,
        name: L10nText.fromJson(j['name']),
        description: L10nText.fromJson(j['description']),
        price: (j['price'] as num?)?.toDouble(),
        image: j['image'] as String?,
        featured: j['featured'] == true,
        options: ((j['options'] as List?) ?? []).map((o) => ItemOption.fromJson(o)).toList(),
        addonGroups: ((j['addon_groups'] as List?) ?? []).map((g) => AddonGroup.fromJson(g)).toList(),
        leadHours: (j['lead_hours'] as num?)?.toInt() ?? 0,
        branchIds: ((j['branch_ids'] as List?) ?? []).map((v) => (v as num).toInt()).toList(),
      );
}

class Category {
  final int id;
  final String slug;
  final L10nText name;
  final L10nText note;
  final List<MenuItem> items;
  Category(this.id, this.slug, this.name, this.note, this.items);

  factory Category.fromJson(Map<String, dynamic> j) => Category(
        j['id'] as int,
        j['slug'] as String,
        L10nText.fromJson(j['name']),
        L10nText.fromJson(j['note']),
        ((j['items'] as List?) ?? []).map((i) => MenuItem.fromJson(i)).toList(),
      );
}

class Branch {
  final int id;
  final L10nText name;
  final L10nText address;
  final List<String> phones;
  final String whatsapp;
  final String? mapUrl;
  final double deliveryFee;
  /// Where the branch is (lat, lng), to send a map pin to the nearest branch.
  final (double, double)? location;
  /// The areas this branch delivers to.
  final List<Area> areas;
  Branch(this.id, this.name, this.address, this.phones, this.whatsapp, this.mapUrl, this.deliveryFee, [this.location, this.areas = const []]);

  factory Branch.fromJson(Map<String, dynamic> j) => Branch(
        j['id'] as int,
        L10nText.fromJson(j['name']),
        L10nText.fromJson(j['address']),
        ((j['phones'] as List?) ?? []).map((e) => '$e').toList(),
        '${j['whatsapp'] ?? ''}',
        j['map_url'] as String?,
        (j['delivery_fee'] as num?)?.toDouble() ?? 0,
        j['location'] is Map ? ((j['location']['lat'] as num).toDouble(), (j['location']['lng'] as num).toDouble()) : null,
        ((j['areas'] as List?) ?? []).map((a) => Area((a['id'] as num).toInt(), L10nText.fromJson(a['name']), (j['id'] as num).toInt())).toList(),
      );
}

/// A delivery area such as Al Khalidiyah, served by one branch.
class Area {
  final int id;
  final L10nText name;
  final int branchId;
  const Area(this.id, this.name, this.branchId);
}

class AppConfig {
  final bool cardPayment;
  final String instagram;
  /// Ordering hours in Abu Dhabi time: first and last choice ("12:00", "23:30"), step in minutes,
  /// days banquets can be booked ahead, and minimum minutes for an ordinary meal.
  final String open, last;
  final int step, days, meal;
  /// Payment methods the owner has switched on, in order: card, cash, whatsapp.
  final List<String> payments;
  /// False while the owner has paused new orders from the admin.
  final bool takingOrders;
  AppConfig(this.cardPayment, this.instagram,
      {this.open = '12:00', this.last = '23:30', this.step = 30, this.days = 30, this.meal = 30, this.payments = const ['whatsapp'], this.takingOrders = true});

  factory AppConfig.fromJson(Map<String, dynamic> j) {
    final h = (j['hours'] as Map?) ?? {};
    return AppConfig(j['card_payment'] == true, '${j['instagram'] ?? ''}',
        open: '${h['open'] ?? '12:00'}', last: '${h['last'] ?? '23:30'}',
        step: (h['step'] as num?)?.toInt() ?? 30, days: (h['days'] as num?)?.toInt() ?? 30, meal: (h['meal'] as num?)?.toInt() ?? 30,
        payments: j['payment_methods'] is List
            ? (j['payment_methods'] as List).map((e) => '$e').toList()
            : [if (j['card_payment'] == true) 'card', 'whatsapp'],
        takingOrders: j['taking_orders'] != false);
  }
}

class PlacedOrder {
  final String code;
  final String status;
  final String paymentStatus;
  final double total;
  /// Stripe or WhatsApp link to open; null for cash orders.
  final String? redirect;
  PlacedOrder(this.code, this.status, this.paymentStatus, this.total, this.redirect);
}

/// A signed-in customer (phone is shown as 050 123 4567).
class Customer {
  final int id;
  final String phone;
  final String? name;
  final String? address;
  final int? branchId;
  final int ordersCount;
  /// Saved map pin (lat, lng) from the last delivery, if any.
  final (double, double)? location;
  final int? areaId;
  Customer(this.id, this.phone, this.name, this.address, this.branchId, this.ordersCount, [this.location, this.areaId]);

  factory Customer.fromJson(Map j) => Customer(
        (j['id'] as num).toInt(),
        j['phone'] as String,
        j['name'] as String?,
        j['address'] as String?,
        (j['branch_id'] as num?)?.toInt(),
        (j['orders_count'] as num?)?.toInt() ?? 0,
        j['location'] is Map ? ((j['location']['lat'] as num).toDouble(), (j['location']['lng'] as num).toDouble()) : null,
        (j['area_id'] as num?)?.toInt(),
      );

  Map<String, dynamic> toJson() => {
        'area_id': areaId,
        'id': id, 'phone': phone, 'name': name, 'address': address, 'branch_id': branchId, 'orders_count': ordersCount,
        'location': location == null ? null : {'lat': location!.$1, 'lng': location!.$2},
      };
}
