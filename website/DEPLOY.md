# موقع ربيع الإمارات للمندي (Laravel)

## ما الموجود
- موقع عربي/إنجليزي: الرئيسية، القائمة، الفروع، السلة، إتمام الطلب، صفحة حالة الطلب
- الطلب عبر واتساب (يفتح محادثة الفرع والطلب مكتوب) أو الدفع أونلاين عبر Stripe
- لوحة تحكم `/admin`: الطلبات وحالتها، تعديل الأصناف والأسعار والصور وإخفاؤها، إعدادات الفرعين ورقم واتساب كل فرع
- واجهة API لتطبيق Flutter: `/api/v1/config`، `/api/v1/menu`، `/api/v1/branches`، `POST /api/v1/orders`، `/api/v1/orders/{code}`
- الأسعار تُحسب دائمًا من قاعدة البيانات، وليس من المتصفح أو التطبيق

## الرفع على Hostinger (Cloud Startup)
1. من hPanel: Websites > rabeeuaemandi.com > Advanced > PHP Configuration، اختر PHP 8.3 أو أحدث.
2. Databases > MySQL: أنشئ قاعدة بيانات ومستخدم.
3. ارفع المشروع (Git أو File Manager) لمجلد خارج `public_html`، مثلاً `~/restaurant`.
4. اجعل `public_html` يشير لمجلد `public` (أو انقل محتويات `public` إلى `public_html` وعدّل المسارات في `index.php`).
5. عبر SSH:
   ```bash
   cd ~/restaurant
   composer install --no-dev --optimize-autoloader
   cp .env.example .env && php artisan key:generate
   # عدّل .env: APP_ENV=production, APP_DEBUG=false, APP_URL=https://rabeeuaemandi.com
   # DB_CONNECTION=mysql و DB_HOST و DB_DATABASE و DB_USERNAME و DB_PASSWORD
   # ADMIN_EMAIL و ADMIN_PASSWORD (كلمة مرور قوية)
   php artisan migrate --force --seed
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
6. فعّل SSL من hPanel.

## Stripe
1. أنشئ حساب Stripe للإمارات وفعّله برخصة المطعم.
2. من Developers > API keys انسخ المفاتيح إلى `.env`: `STRIPE_KEY` و `STRIPE_SECRET`.
3. Developers > Webhooks > Add endpoint: `https://rabeeuaemandi.com/stripe/webhook`
   الأحداث: `checkout.session.completed`، `checkout.session.async_payment_succeeded`، `checkout.session.async_payment_failed`، `checkout.session.expired`.
   انسخ Signing secret إلى `STRIPE_WEBHOOK_SECRET`.
4. `php artisan config:cache`. ابدأ بمفاتيح الاختبار `sk_test_` وجرب بالبطاقة 4242 4242 4242 4242.
   إذا كان `STRIPE_SECRET` فارغًا، يظهر خيار البطاقة معطّلاً ويبقى الطلب عبر واتساب.

## الاختبارات
```bash
php artisan test
```

## Customer accounts, OTP and delivery areas (app)

1. Copy the new code, then run `php artisan migrate --force` (adds customers, customer_otps,
   customer_tokens, areas, and lat/lng/area_id columns; fills the first area lists). Never `--seed`.
2. In `.env` set:
   ```
   OTP_DRIVER=msg91
   MSG91_AUTH_KEY=...           # MSG91 dashboard > Authkey
   MSG91_OTP_TEMPLATE_ID=...    # an approved OTP template (with the ##OTP## variable)
   ```
   Until then `OTP_DRIVER=log` writes codes to storage/logs/laravel.log (testing only).
3. Google Maps key (Google Cloud console, enable "Maps SDK for Android", "Maps SDK for iOS",
   "Maps JavaScript API"): Android `MAPS_API_KEY=...` in `app/android/local.properties`;
   iOS `app/ios/Flutter/Maps.xcconfig` (see Maps.xcconfig.example); web: replace `MAPS_API_KEY`
   in `app/web/index.html` before building. Restrict each key to the app / the domain.
4. Delivery areas per branch: Admin > Branches (one per line, "Arabic | English").
