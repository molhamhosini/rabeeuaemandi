import 'dart:async';

import 'package:material_ui/material_ui.dart';

import '../app_state.dart';
import '../strings.dart';
import '../theme.dart';

/// Sign in with a UAE mobile number: type the number, then the 6-digit code that comes by SMS.
/// Pops with `true` once signed in.
class AuthScreen extends StatefulWidget {
  /// Shown under the title, e.g. "sign in to place your order".
  final String? reason;
  const AuthScreen({super.key, this.reason});

  /// Opens the sign-in screen; true when the customer is signed in afterwards.
  static Future<bool> open(BuildContext context, {String? reason}) async {
    if (AppScope.read(context).signedIn) return true;
    final ok = await Navigator.of(context).push<bool>(MaterialPageRoute(fullscreenDialog: true, builder: (_) => AuthScreen(reason: reason)));
    return ok == true;
  }

  /// 050 123 4567 / +971 50... / 00971... -> 9715XXXXXXXX, or null when it is not a UAE mobile.
  static String? normalize(String raw) {
    var d = raw.replaceAll(RegExp(r'\D'), '');
    if (d.startsWith('00')) d = d.substring(2);
    if (d.startsWith('971')) d = d.substring(3);
    if (d.startsWith('0')) d = d.substring(1);
    return RegExp(r'^5\d{8}$').hasMatch(d) ? '971$d' : null;
  }

  @override
  State<AuthScreen> createState() => _AuthScreenState();
}

class _AuthScreenState extends State<AuthScreen> {
  final _phone = TextEditingController();
  final _code = TextEditingController();
  final _name = TextEditingController();
  String? _sentTo; // 9715XXXXXXXX once a code was sent
  bool _askName = false;
  bool _busy = false;
  String? _error;
  int _wait = 0;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
    _phone.dispose();
    _code.dispose();
    _name.dispose();
    super.dispose();
  }

  void _countdown(int seconds) {
    _timer?.cancel();
    setState(() => _wait = seconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return t.cancel();
      setState(() => _wait = _wait > 0 ? _wait - 1 : 0);
      if (_wait == 0) t.cancel();
    });
  }

  Future<void> _run(Future<void> Function() job) async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await job();
    } catch (e) {
      if (mounted) setState(() => _error = '$e');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _send() async {
    final s = AppScope.read(context);
    final phone = AuthScreen.normalize(_sentTo == null ? _phone.text : '0${_sentTo!.substring(3)}');
    if (phone == null) return setState(() => _error = tr('bad_mobile', s.lang));
    await _run(() async {
      final wait = await s.api.requestCode(phone, s.lang);
      setState(() {
        _sentTo = phone;
        _code.clear();
      });
      _countdown(wait);
    });
  }

  Future<void> _verify() async {
    final s = AppScope.read(context);
    final code = _code.text.trim();
    if (code.length < 4) return;
    await _run(() async {
      final (token, customer, _) = await s.api.verifyCode(_sentTo!, code, s.lang);
      await s.signedInAs(token, customer);
      if (!mounted) return;
      if (customer.name == null || customer.name!.isEmpty) {
        setState(() => _askName = true);
      } else {
        Navigator.of(context).pop(true);
      }
    });
  }

  Future<void> _saveName() async {
    final s = AppScope.read(context);
    final name = _name.text.trim();
    if (name.isEmpty) return Navigator.of(context).pop(true);
    await _run(() async {
      await s.updateProfile(name: name);
      if (mounted) Navigator.of(context).pop(true);
    });
  }

  String _pretty(String p) {
    final l = '0${p.substring(3)}';
    return '${l.substring(0, 3)} ${l.substring(3, 6)} ${l.substring(6)}';
  }

  @override
  Widget build(BuildContext context) {
    final l = AppScope.of(context).lang;
    final t = Theme.of(context).textTheme;
    final Widget step;
    if (_askName) {
      step = Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text(tr('welcome', l), style: t.headlineMedium, textAlign: TextAlign.center),
        const SizedBox(height: 18),
        TextField(
          controller: _name,
          autofocus: true,
          textInputAction: TextInputAction.done,
          onSubmitted: (_) => _saveName(),
          decoration: InputDecoration(labelText: tr('your_name', l)),
        ),
        const SizedBox(height: 16),
        _button(tr('save', l), _saveName),
        TextButton(onPressed: _busy ? null : () => Navigator.of(context).pop(true), child: Text(tr('skip', l))),
      ]);
    } else if (_sentTo == null) {
      step = Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text(tr('sign_in', l), style: t.headlineMedium, textAlign: TextAlign.center),
        const SizedBox(height: 6),
        Text(widget.reason ?? tr('sign_in_sub', l), style: const TextStyle(color: Brand.muted), textAlign: TextAlign.center),
        const SizedBox(height: 22),
        TextField(
          key: const Key('phone'),
          controller: _phone,
          autofocus: true,
          keyboardType: TextInputType.phone,
          textDirection: TextDirection.ltr,
          onSubmitted: (_) => _send(),
          decoration: InputDecoration(
            labelText: tr('mobile', l),
            hintText: '05X XXX XXXX',
            prefixIcon: const Padding(padding: EdgeInsets.symmetric(horizontal: 12), child: Text('🇦🇪', style: TextStyle(fontSize: 20))),
            prefixIconConstraints: const BoxConstraints(minWidth: 0, minHeight: 0),
          ),
        ),
        const SizedBox(height: 16),
        _button(tr('send_code', l), _send),
      ]);
    } else {
      step = Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        Text(tr('enter_code', l), style: t.headlineMedium, textAlign: TextAlign.center),
        const SizedBox(height: 6),
        Text(tr('code_sent_to', l), style: const TextStyle(color: Brand.muted), textAlign: TextAlign.center),
        Text(_pretty(_sentTo!), textDirection: TextDirection.ltr, textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: Brand.brown)),
        const SizedBox(height: 22),
        TextField(
          key: const Key('code'),
          controller: _code,
          autofocus: true,
          keyboardType: TextInputType.number,
          textDirection: TextDirection.ltr,
          textAlign: TextAlign.center,
          maxLength: 6,
          autofillHints: const [AutofillHints.oneTimeCode],
          style: const TextStyle(fontSize: 26, letterSpacing: 10, fontWeight: FontWeight.w800),
          decoration: const InputDecoration(counterText: '', hintText: '••••••'),
          onChanged: (v) {
            if (v.trim().length == 6) _verify();
          },
        ),
        const SizedBox(height: 16),
        _button(tr('verify', l), _verify),
        const SizedBox(height: 6),
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          TextButton(
            onPressed: _busy ? null : () => setState(() {
              _sentTo = null;
              _error = null;
            }),
            child: Text(tr('change_number', l)),
          ),
          TextButton(
            onPressed: _busy || _wait > 0 ? null : _send,
            child: Text(_wait > 0 ? tr('resend_in', l).replaceAll(':s', '$_wait') : tr('resend', l)),
          ),
        ]),
      ]);
    }

    return Scaffold(
      appBar: AppBar(leading: IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.of(context).pop(AppScope.read(context).signedIn))),
      body: SafeArea(
        child: ListView(padding: const EdgeInsets.fromLTRB(24, 8, 24, 24), children: [
          Center(child: Image.asset('assets/images/logo.png', height: 96, errorBuilder: (_, _, _) => const SizedBox(height: 8))),
          const SizedBox(height: 16),
          step,
          if (_error != null) Padding(padding: const EdgeInsets.only(top: 12), child: Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: Brand.red, fontWeight: FontWeight.w600))),
        ]),
      ),
    );
  }

  Widget _button(String label, VoidCallback onTap) => FilledButton(
        onPressed: _busy ? null : onTap,
        child: _busy ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white)) : Text(label),
      );
}

