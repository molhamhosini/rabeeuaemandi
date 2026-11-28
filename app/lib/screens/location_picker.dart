import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:material_ui/material_ui.dart';

import '../app_state.dart';
import '../strings.dart';
import '../theme.dart';

/// Full-screen Google map with a pin in the middle: the customer moves the map until the pin
/// sits on their door, or taps "my location". Pops with the chosen [LatLng].
class LocationPicker extends StatefulWidget {
  final LatLng? initial;
  const LocationPicker({super.key, this.initial});

  /// Abu Dhabi, between the two branches, when we know nothing better.
  static const fallback = LatLng(24.37, 54.62);

  static Future<LatLng?> open(BuildContext context, {LatLng? initial}) =>
      Navigator.of(context).push<LatLng>(MaterialPageRoute(fullscreenDialog: true, builder: (_) => LocationPicker(initial: initial)));

  /// Rough UAE box, same as the server check.
  static bool inUae(LatLng p) => p.latitude >= 22 && p.latitude <= 26.5 && p.longitude >= 51 && p.longitude <= 56.5;

  @override
  State<LocationPicker> createState() => _LocationPickerState();
}

class _LocationPickerState extends State<LocationPicker> {
  GoogleMapController? _map;
  late LatLng _center = widget.initial ?? LocationPicker.fallback;
  bool _locating = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    // nothing saved yet: try to start on where the phone is
    if (widget.initial == null) WidgetsBinding.instance.addPostFrameCallback((_) => _myLocation(quiet: true));
  }

  Future<void> _myLocation({bool quiet = false}) async {
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
      final p = LatLng(pos.latitude, pos.longitude);
      _center = p;
      await _map?.animateCamera(CameraUpdate.newLatLngZoom(p, 17));
    } catch (e) {
      if (!quiet && mounted) setState(() => _error = '$e');
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = AppScope.of(context).lang;
    final ok = LocationPicker.inUae(_center);
    return Scaffold(
      appBar: AppBar(title: Text(tr('pick_location', l))),
      body: Stack(children: [
        GoogleMap(
          initialCameraPosition: CameraPosition(target: _center, zoom: widget.initial == null ? 11 : 17),
          onMapCreated: (c) => _map = c,
          onCameraMove: (p) => _center = p.target,
          onCameraIdle: () => setState(() {}),
          myLocationEnabled: true,
          myLocationButtonEnabled: false,
          zoomControlsEnabled: false,
          mapToolbarEnabled: false,
        ),
        // the pin stays in the middle; its tip marks the spot
        const IgnorePointer(
          child: Center(child: Padding(padding: EdgeInsets.only(bottom: 44), child: Icon(Icons.location_on, size: 48, color: Brand.red))),
        ),
        PositionedDirectional(
          top: 12,
          start: 12,
          end: 12,
          child: Material(
            color: Brand.card,
            elevation: 2,
            borderRadius: BorderRadius.circular(14),
            child: Padding(padding: const EdgeInsets.all(12), child: Text(tr('pick_location_hint', l), style: const TextStyle(fontWeight: FontWeight.w600))),
          ),
        ),
        PositionedDirectional(
          bottom: 16,
          end: 16,
          child: FloatingActionButton(
            heroTag: 'me',
            backgroundColor: Brand.card,
            foregroundColor: Brand.brown,
            onPressed: _locating ? null : _myLocation,
            tooltip: tr('my_location', l),
            child: _locating ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2.5)) : const Icon(Icons.my_location),
          ),
        ),
      ]),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            if (_error != null) Padding(padding: const EdgeInsets.only(bottom: 8), child: Text(_error!, style: const TextStyle(color: Brand.red))),
            if (!ok) Padding(padding: const EdgeInsets.only(bottom: 8), child: Text(tr('outside_uae', l), style: const TextStyle(color: Brand.red))),
            FilledButton(onPressed: ok ? () => Navigator.of(context).pop(_center) : null, child: Text(tr('confirm_location', l))),
          ]),
        ),
      ),
    );
  }
}
