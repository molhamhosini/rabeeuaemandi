import 'package:material_ui/material_ui.dart';
import 'package:video_player/video_player.dart';

import '../app_state.dart';
import '../strings.dart';
import '../theme.dart';
import 'widgets.dart';

class HomeScreen extends StatelessWidget {
  final void Function(String? slug) onOpenMenu;
  const HomeScreen({super.key, required this.onOpenMenu});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    final featured = s.featured;
    return RefreshIndicator(
      color: Brand.gold,
      onRefresh: s.load,
      child: CustomScrollView(slivers: [
        SliverAppBar(
          pinned: true,
          toolbarHeight: 64,
          title: Image.asset('assets/images/logo-wordmark.png', height: 44),
          actions: [
            TextButton(onPressed: s.toggleLang, child: Text(tr('language', l), style: const TextStyle(color: Brand.brown, fontWeight: FontWeight.w700))),
          ],
        ),
        SliverToBoxAdapter(child: _Hero(onOrder: () => onOpenMenu(null))),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
            child: Row(children: [
              _Perk(Icons.local_shipping_outlined, tr('free_delivery', l)),
              const SizedBox(width: 8),
              _Perk(Icons.weekend_outlined, tr('family', l)),
              const SizedBox(width: 8),
              _Perk(Icons.celebration_outlined, tr('banquets', l)),
            ]),
          ),
        ),
        SliverToBoxAdapter(child: SectionTitle(tr('featured', l))),
        const SliverToBoxAdapter(child: BranchPicker()),
        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          sliver: SliverGrid.builder(
            gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(maxCrossAxisExtent: 240, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: .76),
            itemCount: featured.length,
            itemBuilder: (_, i) => DishCard(featured[i]),
          ),
        ),
        SliverToBoxAdapter(child: SectionTitle(tr('sections', l))),
        SliverToBoxAdapter(
          child: SizedBox(
            height: 118,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: s.categories.length,
              separatorBuilder: (_, _) => const SizedBox(width: 10),
              itemBuilder: (_, i) {
                final c = s.categories[i];
                final img = c.items.where((x) => x.image != null).firstOrNull;
                return InkWell(
                  borderRadius: BorderRadius.circular(16),
                  onTap: () => onOpenMenu(c.slug),
                  child: Container(
                    width: 112,
                    decoration: BoxDecoration(color: Brand.card, borderRadius: BorderRadius.circular(16), border: Border.all(color: Brand.line)),
                    child: Column(children: [
                      Expanded(
                        child: ClipRRect(
                          borderRadius: const BorderRadius.vertical(top: Radius.circular(15)),
                          child: img != null ? DishImage(img) : Container(color: Brand.sand, child: const Center(child: Icon(Icons.local_drink_outlined, color: Brand.gold))),
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 4),
                        child: Text(c.name.of(l), maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w700, color: Brand.brown, fontSize: 13)),
                      ),
                    ]),
                  ),
                );
              },
            ),
          ),
        ),
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(tr('vat', l), textAlign: TextAlign.center, style: const TextStyle(color: Brand.muted, fontSize: 12)),
          ),
        ),
      ]),
    );
  }
}

class _Perk extends StatelessWidget {
  final IconData icon;
  final String text;
  const _Perk(this.icon, this.text);

  @override
  Widget build(BuildContext context) => Expanded(
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 6),
          decoration: BoxDecoration(color: Brand.card, borderRadius: BorderRadius.circular(14), border: Border.all(color: Brand.line)),
          child: Column(children: [
            Icon(icon, color: Brand.gold2),
            const SizedBox(height: 4),
            Text(text, textAlign: TextAlign.center, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Brand.brown)),
          ]),
        ),
      );
}

/// Looping promo video in an arch, with the tagline and order button over it.
class _Hero extends StatefulWidget {
  final VoidCallback onOrder;
  const _Hero({required this.onOrder});

  @override
  State<_Hero> createState() => _HeroState();
}

class _HeroState extends State<_Hero> {
  late final VideoPlayerController _video = VideoPlayerController.asset('assets/images/hero.mp4');

  @override
  void initState() {
    super.initState();
    _video
      ..setLooping(true)
      ..setVolume(0)
      ..initialize().then((_) {
        if (mounted) setState(() {});
        _video.play();
      }).catchError((_) {});
  }

  @override
  void dispose() {
    _video.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l = AppScope.of(context).lang;
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: ClipRRect(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(160), bottom: Radius.circular(24)),
        child: SizedBox(
          height: 420,
          child: Stack(fit: StackFit.expand, children: [
            _video.value.isInitialized
                ? FittedBox(fit: BoxFit.cover, child: SizedBox(width: _video.value.size.width, height: _video.value.size.height, child: VideoPlayer(_video)))
                : Image.asset('assets/images/hero-poster.jpg', fit: BoxFit.cover),
            const DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(begin: Alignment.topCenter, end: Alignment.bottomCenter, colors: [Colors.transparent, Color(0xCC2F2A24)], stops: [.45, 1]),
              ),
            ),
            Positioned(
              left: 20,
              right: 20,
              bottom: 20,
              child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                Text(tr('tagline', l), textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 17, fontWeight: FontWeight.w700, height: 1.4)),
                const SizedBox(height: 14),
                FilledButton(
                  style: FilledButton.styleFrom(backgroundColor: Brand.gold),
                  onPressed: widget.onOrder,
                  child: Text(tr('order_now', l)),
                ),
              ]),
            ),
          ]),
        ),
      ),
    );
  }
}
