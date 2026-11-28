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

  /// The signed-in customer's token; sent with every request once set.
  String? token;

  Map<String, String> _h(String lang, {bool json = false}) => {
        'Accept': 'application/json',
        'X-Locale': lang,
        if (json) 'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  /// Reads a JSON reply; turns Laravel's validation errors into one readable message.
  dynamic _read(http.Response res, {int ok = 200}) {
    final data = res.bodyBytes.isEmpty ? null : jsonDecode(utf8.decode(res.bodyBytes));
    if (res.statusCode == 401) throw UnauthorizedException();
    if (res.statusCode != ok) {
      final errors = data is Map && data['errors'] is Map
          ? (data['errors'] as Map).values.expand((e) => e is List ? e : [e]).join('\n')
          : (data is Map && data['message'] != null ? '${data['message']}' : 'HTTP ${res.statusCode}');
      throw ApiException(errors);
    }
    return data;
  }

  Future<dynamic> _send(String method, String path, String lang, [Map<String, dynamic>? body]) async {
    final req = http.Request(method, _u(path))..headers.addAll(_h(lang, json: true));
    if (body != null) req.body = jsonEncode(body);
    return _read(await http.Response.fromStream(await _client.send(req)));
  }

  // --- customer accounts (sign in with a mobile number + one-time code)

  /// Sends the code; returns seconds until a new code can be asked for.
  Future<int> requestCode(String phone, String lang) async =>
      ((await _send('POST', 'auth/otp', lang, {'phone': phone}))['resend_in'] as num).toInt();

  /// Checks the code; returns the new token and the account.
  Future<(String, Customer, bool)> verifyCode(String phone, String code, String lang) async {
    final d = await _send('POST', 'auth/verify', lang, {'phone': phone, 'code': code, 'device': 'app'});
    return (d['token'] as String, Customer.fromJson(d['customer']), d['is_new'] == true);
  }

  Future<Customer> me(String lang) async => Customer.fromJson(await _send('GET', 'me', lang));

  Future<Customer> updateMe(Map<String, dynamic> fields, String lang) async => Customer.fromJson(await _send('PATCH', 'me', lang, fields));

  Future<List<Map<String, dynamic>>> myOrders(String lang) async =>
      (await _send('GET', 'me/orders', lang) as List).map((o) => Map<String, dynamic>.from(o)).toList();

  Future<void> logout(String lang) async => _send('POST', 'auth/logout', lang);

  Future<void> deleteAccount(String lang) async => _send('DELETE', 'me', lang);

  Future<dynamic> _get(String path, String lang) async {
    final res = await _client.get(_u(path), headers: _h(lang));
    if (res.statusCode != 200) throw ApiException('HTTP ${res.statusCode}');
    return jsonDecode(utf8.decode(res.bodyBytes));
  }

  Future<AppConfig> config(String lang) async => AppConfig.fromJson(await _get('config', lang));

  Future<List<Category>> menu(String lang) async =>
      (await _get('menu', lang) as List).map((c) => Category.fromJson(c)).toList();

  Future<List<Branch>> branches(String lang) async =>
      (await _get('branches', lang) as List).map((b) => Branch.fromJson(b)).toList();

  Future<PlacedOrder> placeOrder(Map<String, dynamic> body, String lang) async {
    final res = await _client.post(_u('orders'), headers: _h(lang, json: true), body: jsonEncode(body));
    final data = _read(res, ok: 201);
    final o = data['order'];
    return PlacedOrder(o['code'], o['status'], o['payment_status'], (o['total'] as num).toDouble(), data['redirect'] as String?);
  }

  Future<Map<String, dynamic>> order(String code, String lang) async =>
      Map<String, dynamic>.from(await _get('orders/$code', lang));
}

/// The saved sign-in is no longer valid (signed out elsewhere or account deleted).
class UnauthorizedException implements Exception {}

class ApiException implements Exception {
  final String message;
  ApiException(this.message);
  @override
  String toString() => message;
}
