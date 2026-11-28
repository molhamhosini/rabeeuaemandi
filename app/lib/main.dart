import 'package:material_ui/material_ui.dart';
import 'package:provider/provider.dart';

import 'api.dart';
import 'app_state.dart';
import 'screens/account_screen.dart';
import 'screens/branches_screen.dart';
import 'screens/cart_screen.dart';
import 'screens/home_screen.dart';
import 'screens/location_gate.dart';
import 'screens/menu_screen.dart';
import 'screens/splash_screen.dart';
import 'strings.dart';
import 'theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(ChangeNotifierProvider(create: (_) => AppState(Api())..init(), child: const RabeeApp()));
}

class RabeeApp extends StatelessWidget {
  const RabeeApp({super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: tr('brand', s.lang),
      theme: buildTheme(),
      locale: Locale(s.lang),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: GlobalMaterialLocalizations.delegates,
      home: const Shell(),
    );
  }
}

class Shell extends StatefulWidget {
  const Shell({super.key});

  static void goTo(BuildContext context, int tab) => context.findAncestorStateOfType<_ShellState>()?.setTab(tab);

  @override
  State<Shell> createState() => _ShellState();
}

class _ShellState extends State<Shell> {
  int tab = 0;
  String? menuCategory;
  /// The splash stays at least this long so the logo is seen, then until the menu has loaded.
  bool _splashTimeUp = false;

  @override
  void initState() {
    super.initState();
    Future.delayed(const Duration(milliseconds: 1800), () {
      if (mounted) setState(() => _splashTimeUp = true);
    });
  }

  void setTab(int t, {String? category}) => setState(() {
        tab = t;
        menuCategory = category;
      });

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final l = s.lang;
    final loaded = !(s.loading && s.categories.isEmpty);
    if (!_splashTimeUp || (!loaded && s.error == null)) return const SplashScreen();
    // first run: ask where to deliver before showing the menu
    if (s.place == null && s.categories.isNotEmpty) return const LocationGate(firstRun: true);
    final Widget body;
    if (!loaded) {
      body = const Center(child: CircularProgressIndicator(color: Brand.gold));
    } else if (s.error != null && s.categories.isEmpty) {
      body = Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.wifi_off_rounded, size: 48, color: Brand.gold),
          const SizedBox(height: 12),
          Text(tr('offline', l), style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 16),
          FilledButton(onPressed: s.load, child: Text(tr('retry', l))),
        ]),
      );
    } else {
      body = IndexedStack(index: tab, children: [
        HomeScreen(onOpenMenu: (slug) => setTab(1, category: slug)),
        MenuScreen(initialCategory: menuCategory),
        const CartScreen(),
        const BranchesScreen(),
        const AccountScreen(),
      ]);
    }
    return Scaffold(
      body: body,
      bottomNavigationBar: NavigationBar(
        selectedIndex: tab,
        onDestinationSelected: (t) => setTab(t),
        destinations: [
          NavigationDestination(icon: const Icon(Icons.home_outlined), selectedIcon: const Icon(Icons.home_rounded), label: tr('home', l)),
          NavigationDestination(icon: const Icon(Icons.restaurant_menu_outlined), selectedIcon: const Icon(Icons.restaurant_menu), label: tr('menu', l)),
          NavigationDestination(
            icon: Badge(isLabelVisible: s.count > 0, label: Text('${s.count}'), backgroundColor: Brand.gold, child: const Icon(Icons.shopping_bag_outlined)),
            selectedIcon: Badge(isLabelVisible: s.count > 0, label: Text('${s.count}'), backgroundColor: Brand.gold, child: const Icon(Icons.shopping_bag)),
            label: tr('cart', l),
          ),
          NavigationDestination(icon: const Icon(Icons.storefront_outlined), selectedIcon: const Icon(Icons.storefront), label: tr('branches', l)),
          NavigationDestination(icon: const Icon(Icons.person_outline_rounded), selectedIcon: const Icon(Icons.person_rounded), label: tr('account', l)),
        ],
      ),
    );
  }
}
