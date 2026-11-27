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
