import 'package:material_ui/material_ui.dart';
import 'package:url_launcher/url_launcher.dart';

import '../app_state.dart';
import '../strings.dart';
import '../theme.dart';

class BranchesScreen extends StatelessWidget {
  const BranchesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    void open(String url) => launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    return Scaffold(
      appBar: AppBar(title: Text(tr('branches', l))),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        for (final b in s.branches)
          Container(
            margin: const EdgeInsets.only(bottom: 14),
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(color: Brand.card, borderRadius: BorderRadius.circular(18), border: Border.all(color: Brand.line)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(b.name.of(l), style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 4),
              Row(children: [
                const Icon(Icons.place_outlined, size: 18, color: Brand.muted),
                const SizedBox(width: 4),
                Expanded(child: Text(b.address.of(l), style: const TextStyle(color: Brand.muted))),
              ]),
              const SizedBox(height: 10),
              Wrap(spacing: 8, runSpacing: 8, children: [
                for (final p in b.phones)
                  ActionChip(
                    label: Text(p, textDirection: TextDirection.ltr, style: const TextStyle(fontWeight: FontWeight.w700, color: Brand.brown)),
                    backgroundColor: Brand.sand,
                    side: BorderSide.none,
                    onPressed: () => open('tel:${p.replaceAll(' ', '')}'),
                  ),
              ]),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(
                  child: FilledButton.icon(
                    style: FilledButton.styleFrom(backgroundColor: Brand.whatsapp, minimumSize: const Size(0, 44)),
                    onPressed: () => open('https://wa.me/${b.whatsapp}'),
                    icon: const Icon(Icons.chat_outlined, size: 18),
                    label: Text(tr('whatsapp', l)),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(minimumSize: const Size(0, 44), foregroundColor: Brand.brown, shape: const StadiumBorder()),
                    onPressed: () => open(b.mapUrl ??
                        'https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent('ربيع الإمارات للمندي ${b.address.ar}')}'),
                    icon: const Icon(Icons.map_outlined, size: 18),
                    label: Text(tr('location', l)),
                  ),
                ),
              ]),
            ]),
          ),
        if (s.config.instagram.isNotEmpty)
          TextButton.icon(
            onPressed: () => open(s.config.instagram),
            icon: const Icon(Icons.camera_alt_outlined, color: Brand.brown),
            label: const Text('@rabeeemaratmandi', style: TextStyle(color: Brand.brown, fontWeight: FontWeight.w700)),
          ),
      ]),
    );
  }
}
