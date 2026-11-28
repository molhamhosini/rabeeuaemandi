import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart' show LatLng;
import 'package:material_ui/material_ui.dart';

import '../app_state.dart';
import '../models.dart';
import '../strings.dart';
import '../theme.dart';
import 'location_picker.dart';

/// Shown after the splash (and from the location bar): where should we deliver? The answer picks
/// the branch, so the menu shows only what that branch makes. Pops with true when chosen.
class LocationGate extends StatefulWidget {
  /// First run: no close button, the app waits for an answer.
  final bool firstRun;
  const LocationGate({super.key, this.firstRun = false});

  static Future<void> open(BuildContext context) =>
      Navigator.of(context).push(MaterialPageRoute(fullscreenDialog: true, builder: (_) => const LocationGate()));

  @override
  State<LocationGate> createState() => _LocationGateState();
}

class _LocationGateState extends State<LocationGate> {
  bool _locating = false;
  String? _error;

  Future<void> _done(DeliveryPlace p) async {
    await AppScope.read(context).setPlace(p);
    if (mounted && !widget.firstRun) Navigator.of(context).pop(true);
  }

  Future<void> _useMyLocation() async {
    final l = AppScope.read(context).lang;
    setState(() {
      _locating = true;
      _error = null;
    });
    try {
      if (!await Geolocator.isLocationServiceEnabled()) throw tr('loc_off', l);
      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) perm = await Geolocator.requestPermission();
      if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) throw tr('loc_denied', l);
      final pos = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, timeLimit: Duration(seconds: 15)));
      if (!LocationPicker.inUae(LatLng(pos.latitude, pos.longitude))) throw tr('outside_uae', l);
      await _done(DeliveryPlace.pin(pos.latitude, pos.longitude));
    } catch (e) {
      if (mounted) setState(() => _error = '$e');
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _onMap() async {
    final s = AppScope.read(context);
    final p = s.place;
    final pin = await LocationPicker.open(context, initial: p?.kind == 'pin' ? LatLng(p!.lat!, p.lng!) : null);
    if (pin != null) await _done(DeliveryPlace.pin(pin.latitude, pin.longitude));
  }

  Future<void> _pickArea() async {
    final s = AppScope.read(context);
    final l = s.lang;
    final id = await showModalBottomSheet<int>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Brand.cream,
      showDragHandle: true,
      builder: (ctx) => DraggableScrollableSheet(
        expand: false,
        initialChildSize: .75,
        maxChildSize: .92,
        builder: (_, scroll) => ListView(controller: scroll, padding: const EdgeInsets.fromLTRB(16, 0, 16, 24), children: [
          Text(tr('by_area', l), style: Theme.of(ctx).textTheme.titleLarge, textAlign: TextAlign.center),
          for (final b in s.branches) ...[
            Padding(
              padding: const EdgeInsets.fromLTRB(4, 18, 4, 6),
              child: Text(b.name.of(l), style: const TextStyle(fontWeight: FontWeight.w800, color: Brand.gold2)),
            ),
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final a in b.areas)
                ActionChip(
                  label: Text(a.name.of(l)),
                  backgroundColor: s.place?.areaId == a.id ? Brand.sand : Colors.white,
                  side: const BorderSide(color: Brand.line),
                  onPressed: () => Navigator.pop(ctx, a.id),
                ),
            ]),
          ],
        ]),
      ),
    );
    if (id != null) await _done(DeliveryPlace.area(id));
  }

  Future<void> _pickup() async {
    final s = AppScope.read(context);
    final l = s.lang;
    final id = await showModalBottomSheet<int>(
      context: context,
      backgroundColor: Brand.cream,
      showDragHandle: true,
      builder: (ctx) => SafeArea(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Text(tr('pickup_from', l), style: Theme.of(ctx).textTheme.titleLarge),
          for (final b in s.branches)
            ListTile(
              leading: const Icon(Icons.storefront, color: Brand.gold2),
              title: Text(b.name.of(l), style: const TextStyle(fontWeight: FontWeight.w700)),
              subtitle: Text(b.address.of(l)),
              onTap: () => Navigator.pop(ctx, b.id),
            ),
          const SizedBox(height: 8),
        ]),
      ),
    );
    if (id != null) await _done(DeliveryPlace.pickup(id));
  }

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: !widget.firstRun,
        actions: [TextButton(onPressed: s.toggleLang, child: Text(tr('language', l)))],
      ),
      body: SafeArea(
        child: ListView(padding: const EdgeInsets.fromLTRB(20, 0, 20, 24), children: [
          Image.asset('assets/images/logo.png', height: 92),
          const SizedBox(height: 14),
          Text(tr('gate_title', l), textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineMedium),
          const SizedBox(height: 6),
          Text(tr('gate_sub', l), textAlign: TextAlign.center, style: const TextStyle(color: Brand.muted)),
          const SizedBox(height: 22),
          _Option(
            key: const Key('gate-me'),
            icon: Icons.my_location,
            title: tr('my_location', l),
            subtitle: tr('gate_me_sub', l),
            primary: true,
            busy: _locating,
            onTap: _locating ? null : _useMyLocation,
          ),
          _Option(key: const Key('gate-map'), icon: Icons.map_outlined, title: tr('set_on_map', l), subtitle: tr('gate_map_sub', l), onTap: _onMap),
          _Option(key: const Key('gate-area'), icon: Icons.list_alt, title: tr('by_area', l), subtitle: tr('gate_area_sub', l), onTap: _pickArea),
          _Option(key: const Key('gate-pickup'), icon: Icons.storefront_outlined, title: tr('pickup_from', l), subtitle: tr('gate_pickup_sub', l), onTap: _pickup),
          if (_error != null) Padding(padding: const EdgeInsets.only(top: 8), child: Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: Brand.red, fontWeight: FontWeight.w600))),
        ]),
      ),
    );
  }
}

class _Option extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback? onTap;
  final bool primary;
  final bool busy;
  const _Option({super.key, required this.icon, required this.title, required this.subtitle, this.onTap, this.primary = false, this.busy = false});

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Material(
          color: primary ? Brand.brown : Brand.card,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18), side: BorderSide(color: primary ? Brand.brown : Brand.line, width: 1.5)),
          child: InkWell(
            borderRadius: BorderRadius.circular(18),
            onTap: onTap,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(children: [
                CircleAvatar(
                  radius: 22,
                  backgroundColor: primary ? Colors.white24 : Brand.sand,
                  child: busy
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white))
                      : Icon(icon, color: primary ? Colors.white : Brand.gold2),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(title, style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: primary ? Colors.white : Brand.brown)),
                    Text(subtitle, style: TextStyle(fontSize: 13, color: primary ? Colors.white70 : Brand.muted)),
                  ]),
                ),
                Icon(Icons.chevron_left, color: primary ? Colors.white70 : Brand.gold2, textDirection: TextDirection.ltr),
              ]),
            ),
          ),
        ),
      );
}
