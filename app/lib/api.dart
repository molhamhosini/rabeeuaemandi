import 'dart:convert';

import 'package:http/http.dart' as http;

import 'models.dart';

/// Talks to the Laravel backend. Point it at a different server with
/// `flutter run --dart-define=API_BASE=https://staging.example.com`.
class Api {
  static const base = String.fromEnvironment('API_BASE', defaultValue: 'https://rabeeuaemandi.com');

  final http.Client _client;
  Api([http.Client? client]) : _client = client ?? http.Client();

  Uri _u(String path) => Uri.parse('$base/api/v1/$path');

  Future<dynamic> _get(String path, String lang) async {
    final res = await _client.get(_u(path), headers: {'Accept': 'application/json', 'X-Locale': lang});
    if (res.statusCode != 200) throw ApiException('HTTP ${res.statusCode}');
    return jsonDecode(utf8.decode(res.bodyBytes));
  }

  Future<AppConfig> config(String lang) async => AppConfig.fromJson(await _get('config', lang));

  Future<List<Category>> menu(String lang) async =>
      (await _get('menu', lang) as List).map((c) => Category.fromJson(c)).toList();

  Future<List<Branch>> branches(String lang) async =>
      (await _get('branches', lang) as List).map((b) => Branch.fromJson(b)).toList();

  Future<PlacedOrder> placeOrder(Map<String, dynamic> body, String lang) async {
    final res = await _client.post(
      _u('orders'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Locale': lang},
      body: jsonEncode(body),
    );
    final data = jsonDecode(utf8.decode(res.bodyBytes));
    if (res.statusCode != 201) {
      final errors = data is Map && data['errors'] is Map
          ? (data['errors'] as Map).values.expand((e) => e is List ? e : [e]).join('\n')
          : (data is Map ? '${data['message']}' : 'Error');
      throw ApiException(errors);
    }
    final o = data['order'];
    return PlacedOrder(o['code'], o['status'], o['payment_status'], (o['total'] as num).toDouble(), data['redirect']);
  }

  Future<Map<String, dynamic>> order(String code, String lang) async =>
      Map<String, dynamic>.from(await _get('orders/$code', lang));
}

class ApiException implements Exception {
  final String message;
  ApiException(this.message);
  @override
  String toString() => message;
}
