# تطبيق ربيع الإمارات للمندي (Flutter)

تطبيق أندرويد وآيفون يقرأ القائمة والفروع من نفس خادم الموقع (Laravel) ويرسل الطلبات له.

## الشاشات
- الرئيسية: فيديو المطعم، الأكثر طلبًا، أقسام القائمة
- القائمة: تبويبات الأقسام، واختيار نوع الذبيحة أو حجم الهريس
- السلة وإتمام الطلب: اختيار الفرع، توصيل أو استلام، الدفع بالبطاقة (Stripe) أو الطلب عبر واتساب
- حالة الطلب، والفروع (اتصال، واتساب، الموقع)
- عربي وإنجليزي، والخطوط مضمّنة (Tajawal و Reem Kufi)

## التشغيل
```bash
flutter pub get
flutter run --dart-define=API_BASE=https://rabeeuaemandi.com
```

## البناء للنشر
```bash
flutter build appbundle --dart-define=API_BASE=https://rabeeuaemandi.com   # Google Play
flutter build ipa       --dart-define=API_BASE=https://rabeeuaemandi.com   # App Store (يحتاج جهاز Mac)
```

قبل النشر: أيقونة التطبيق، وحساب مطوّر Google Play (25 دولار مرة واحدة) وحساب Apple Developer (99 دولار سنويًا).

Flutter 3.50 (Material via `material_ui`).
