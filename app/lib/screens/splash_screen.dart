import 'package:material_ui/material_ui.dart';

import '../theme.dart';

/// First thing people see: the gold logo on the dark brown of the website's hero, fading in
/// while the menu loads.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: Brand.night,
        body: Container(
          decoration: const BoxDecoration(
            gradient: RadialGradient(colors: [Color(0xFF4A3220), Brand.night], radius: 1.1),
          ),
          child: Center(
            child: TweenAnimationBuilder<double>(
              tween: Tween(begin: 0, end: 1),
              duration: const Duration(milliseconds: 1100),
              curve: Curves.easeOutCubic,
              builder: (_, t, child) => Opacity(opacity: t, child: Transform.scale(scale: .85 + .15 * t, child: child)),
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Image.asset('assets/images/logo.png', width: 210),
                const SizedBox(height: 40),
                const SizedBox(width: 26, height: 26, child: CircularProgressIndicator(strokeWidth: 2.5, color: Brand.goldLight)),
              ]),
            ),
          ),
        ),
      );
}
