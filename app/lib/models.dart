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

class MenuItem {
  final int id;
  final int categoryId;
  final L10nText name;
  final L10nText description;
  final double? price;
  final String? image;
  final bool featured;
  final List<ItemOption> options;

  MenuItem({
    required this.id,
    required this.categoryId,
    required this.name,
    required this.description,
    required this.price,
    required this.image,
    required this.featured,
    required this.options,
  });

  bool get hasOptions => options.isNotEmpty;
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
  Branch(this.id, this.name, this.address, this.phones, this.whatsapp, this.mapUrl, this.deliveryFee);

  factory Branch.fromJson(Map<String, dynamic> j) => Branch(
        j['id'] as int,
        L10nText.fromJson(j['name']),
        L10nText.fromJson(j['address']),
        ((j['phones'] as List?) ?? []).map((e) => '$e').toList(),
        '${j['whatsapp'] ?? ''}',
        j['map_url'] as String?,
        (j['delivery_fee'] as num?)?.toDouble() ?? 0,
      );
}

class AppConfig {
  final bool cardPayment;
  final String instagram;
  AppConfig(this.cardPayment, this.instagram);

  factory AppConfig.fromJson(Map<String, dynamic> j) =>
      AppConfig(j['card_payment'] == true, '${j['instagram'] ?? ''}');
}

class PlacedOrder {
  final String code;
  final String status;
  final String paymentStatus;
  final double total;
  final String redirect;
  PlacedOrder(this.code, this.status, this.paymentStatus, this.total, this.redirect);
}
