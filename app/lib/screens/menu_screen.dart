import 'package:material_ui/material_ui.dart';

import '../app_state.dart';
import '../strings.dart';
import '../theme.dart';
import 'widgets.dart';

class MenuScreen extends StatefulWidget {
  final String? initialCategory;
  const MenuScreen({super.key, this.initialCategory});

  @override
  State<MenuScreen> createState() => _MenuScreenState();
}

class _MenuScreenState extends State<MenuScreen> with TickerProviderStateMixin {
  TabController? _tabs;

  @override
  void didUpdateWidget(covariant MenuScreen old) {
    super.didUpdateWidget(old);
    if (widget.initialCategory != old.initialCategory) _jump();
  }

  void _jump() {
    final cats = AppScope.read(context).categories;
    final i = cats.indexWhere((c) => c.slug == widget.initialCategory);
    if (i >= 0) _tabs?.animateTo(i);
  }

  @override
  void dispose() {
    _tabs?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    final cats = s.categories;
    if (_tabs == null || _tabs!.length != cats.length) {
      _tabs?.dispose();
      final start = cats.indexWhere((c) => c.slug == widget.initialCategory);
      _tabs = TabController(length: cats.length, vsync: this, initialIndex: start < 0 ? 0 : start);
    }
    return Scaffold(
      appBar: AppBar(
        title: Text(tr('menu', l)),
        bottom: TabBar(
          controller: _tabs,
          isScrollable: true,
          tabAlignment: TabAlignment.start,
          labelColor: Colors.white,
          unselectedLabelColor: Brand.brown,
          labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontFamily: 'Tajawal'),
          indicatorSize: TabBarIndicatorSize.tab,
          dividerColor: Brand.line,
          indicator: BoxDecoration(color: Brand.brown, borderRadius: BorderRadius.circular(999)),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          tabs: [for (final c in cats) Tab(height: 36, child: Padding(padding: const EdgeInsets.symmetric(horizontal: 6), child: Text(c.name.of(l))))],
        ),
      ),
      body: TabBarView(
        controller: _tabs,
        children: [
          for (final c in cats)
            CustomScrollView(slivers: [
              const SliverToBoxAdapter(child: BranchPicker()),
              if (!c.note.isEmpty)
                SliverToBoxAdapter(
                  child: Padding(padding: const EdgeInsets.fromLTRB(16, 14, 16, 0), child: Text(c.note.of(l), style: const TextStyle(color: Brand.muted))),
                ),
              SliverPadding(
                padding: const EdgeInsets.all(16),
                sliver: SliverGrid.builder(
                  gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(maxCrossAxisExtent: 240, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: .76),
                  itemCount: s.itemsOf(c).length,
                  itemBuilder: (_, i) => DishCard(s.itemsOf(c)[i]),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 24),
                  child: Text(tr('vat', l), textAlign: TextAlign.center, style: const TextStyle(color: Brand.red, fontSize: 12, fontWeight: FontWeight.w700)),
                ),
              ),
            ]),
        ],
      ),
    );
  }
}
