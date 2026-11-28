import 'package:material_ui/material_ui.dart';

class Brand {
  static const cream = Color(0xFFFBF4E4);
  static const sand = Color(0xFFF5E6C4);
  static const sand2 = Color(0xFFEED9AE);
  static const gold = Color(0xFFB5874F);
  static const gold2 = Color(0xFF9A6E3B);
  static const brown = Color(0xFF5B3E22);
  static const ink = Color(0xFF2F2A24);
  static const muted = Color(0xFF7A6E60);
  static const line = Color(0xFFE6D6B3);
  static const card = Color(0xFFFFFDF8);
  static const red = Color(0xFFB23A2A);
  static const whatsapp = Color(0xFF25D366);
  /// dark brown of the website hero, and its gold
  static const night = Color(0xFF2A1C11);
  static const goldLight = Color(0xFFD9B27C);
}

ThemeData buildTheme() {
  final base = ThemeData(
    useMaterial3: true,
    fontFamily: 'Tajawal',
    colorScheme: ColorScheme.fromSeed(seedColor: Brand.brown, primary: Brand.brown, secondary: Brand.gold, surface: Brand.cream),
    scaffoldBackgroundColor: Brand.cream,
  );
  final text = base.textTheme.apply(fontFamily: 'Tajawal').apply(bodyColor: Brand.ink, displayColor: Brand.brown);
  return base.copyWith(
    textTheme: text.copyWith(
      headlineLarge: const TextStyle(fontFamily: 'ReemKufi', fontSize: 30, fontWeight: FontWeight.w600, color: Brand.brown),
      headlineMedium: const TextStyle(fontFamily: 'ReemKufi', fontSize: 24, fontWeight: FontWeight.w600, color: Brand.brown),
      titleLarge: const TextStyle(fontFamily: 'ReemKufi', fontSize: 20, fontWeight: FontWeight.w600, color: Brand.brown),
    ),
    appBarTheme: AppBarTheme(
      backgroundColor: Brand.cream,
      surfaceTintColor: Colors.transparent,
      foregroundColor: Brand.brown,
      elevation: 0,
      centerTitle: true,
      titleTextStyle: const TextStyle(fontFamily: 'ReemKufi', fontSize: 20, fontWeight: FontWeight.w600, color: Brand.brown),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: Brand.brown,
        foregroundColor: Colors.white,
        minimumSize: const Size(0, 52),
        shape: const StadiumBorder(),
        textStyle: const TextStyle(fontFamily: 'Tajawal', fontWeight: FontWeight.w700, fontSize: 16),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Brand.line)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Brand.line, width: 1.5)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Brand.gold, width: 1.5)),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Brand.card,
      indicatorColor: Brand.sand,
      labelTextStyle: WidgetStatePropertyAll(const TextStyle(fontFamily: 'Tajawal', fontWeight: FontWeight.w700, fontSize: 12, color: Brand.brown)),
    ),
    snackBarTheme: const SnackBarThemeData(behavior: SnackBarBehavior.floating, backgroundColor: Brand.ink),
  );
}
