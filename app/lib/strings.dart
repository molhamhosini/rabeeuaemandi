/// UI strings in Arabic and English.
const Map<String, Map<String, String>> _s = {
  'brand': {'ar': 'ربيع الإمارات للمندي', 'en': 'Rabee Al Emarat Mandi'},
  'tagline': {'ar': 'مندي ومظبي وذبائح على أصولها، من مطبخنا لسفرتكم', 'en': 'Authentic mandi, madhbi and whole lamb, from our kitchen to your table'},
  'home': {'ar': 'الرئيسية', 'en': 'Home'},
  'menu': {'ar': 'القائمة', 'en': 'Menu'},
  'cart': {'ar': 'السلة', 'en': 'Cart'},
  'branches': {'ar': 'الفروع', 'en': 'Branches'},
  'order_now': {'ar': 'اطلب الآن', 'en': 'Order now'},
  'featured': {'ar': 'الأكثر طلبًا', 'en': 'Most ordered'},
  'sections': {'ar': 'أقسام القائمة', 'en': 'Menu sections'},
  'aed': {'ar': 'د.إ', 'en': 'AED'},
  'from': {'ar': 'من', 'en': 'from'},
  'add': {'ar': 'أضف', 'en': 'Add'},
  'choose': {'ar': 'اختر', 'en': 'Choose'},
  'added': {'ar': 'تمت الإضافة للسلة', 'en': 'Added to cart'},
  'free_delivery': {'ar': 'توصيل مجاني', 'en': 'Free delivery'},
  'family': {'ar': 'جلسات عائلية', 'en': 'Family seating'},
  'banquets': {'ar': 'عزائم وذبائح', 'en': 'Banquets'},
  'vat': {'ar': 'جميع الأسعار شاملة ضريبة القيمة المضافة', 'en': 'All prices include VAT'},
  'empty': {'ar': 'السلة فارغة', 'en': 'Your cart is empty'},
  'empty_sub': {'ar': 'أضف أطباقك المفضلة من القائمة', 'en': 'Add your favourite dishes from the menu'},
  'subtotal': {'ar': 'المجموع', 'en': 'Subtotal'},
  'delivery': {'ar': 'التوصيل', 'en': 'Delivery'},
  'free': {'ar': 'مجاني', 'en': 'Free'},
  'total': {'ar': 'الإجمالي', 'en': 'Total'},
  'checkout': {'ar': 'إتمام الطلب', 'en': 'Checkout'},
  'branch': {'ar': 'الفرع', 'en': 'Branch'},
  'fulfillment': {'ar': 'طريقة الاستلام', 'en': 'How do you want it?'},
  'deliver': {'ar': 'توصيل', 'en': 'Delivery'},
  'pickup': {'ar': 'استلام من المطعم', 'en': 'Pickup'},
  'name': {'ar': 'الاسم', 'en': 'Name'},
  'phone': {'ar': 'رقم الجوال', 'en': 'Mobile number'},
  'address': {'ar': 'العنوان بالتفصيل', 'en': 'Full address'},
  'notes': {'ar': 'ملاحظات', 'en': 'Notes'},
  'payment': {'ar': 'طريقة الدفع', 'en': 'Payment'},
  'pay_card': {'ar': 'دفع أونلاين بالبطاقة', 'en': 'Pay online by card'},
  'pay_card_sub': {'ar': 'فيزا، ماستركارد، Apple Pay، Google Pay', 'en': 'Visa, Mastercard, Apple Pay, Google Pay'},
  'pay_wa': {'ar': 'اطلب عبر واتساب', 'en': 'Order via WhatsApp'},
  'pay_wa_sub': {'ar': 'نرسل طلبك للمطعم وتدفع عند الاستلام', 'en': 'Send to the restaurant, pay on delivery'},
  'card_off': {'ar': 'الدفع بالبطاقة غير متاح حاليًا', 'en': 'Card payment is not available right now'},
  'submit_card': {'ar': 'ادفع الآن', 'en': 'Pay now'},
  'submit_wa': {'ar': 'أرسل الطلب عبر واتساب', 'en': 'Send order on WhatsApp'},
  'required': {'ar': 'مطلوب', 'en': 'Required'},
  'thanks': {'ar': 'شكرًا لطلبك!', 'en': 'Thank you for your order!'},
  'order_no': {'ar': 'طلب رقم', 'en': 'Order'},
  'status': {'ar': 'حالة الطلب', 'en': 'Status'},
  'refresh': {'ar': 'تحديث', 'en': 'Refresh'},
  'back_home': {'ar': 'العودة للرئيسية', 'en': 'Back to home'},
  'retry': {'ar': 'إعادة المحاولة', 'en': 'Try again'},
  'offline': {'ar': 'تعذر الاتصال بالخادم', 'en': 'Could not reach the server'},
  'call': {'ar': 'اتصال', 'en': 'Call'},
  'whatsapp': {'ar': 'واتساب', 'en': 'WhatsApp'},
  'location': {'ar': 'الموقع', 'en': 'Location'},
  'language': {'ar': 'English', 'en': 'العربية'},
  'st_new': {'ar': 'جديد', 'en': 'New'},
  'st_confirmed': {'ar': 'مؤكد', 'en': 'Confirmed'},
  'st_preparing': {'ar': 'قيد التحضير', 'en': 'Preparing'},
  'st_out_for_delivery': {'ar': 'في الطريق', 'en': 'On the way'},
  'st_completed': {'ar': 'مكتمل', 'en': 'Completed'},
  'st_cancelled': {'ar': 'ملغي', 'en': 'Cancelled'},
  'pay_unpaid': {'ar': 'عند الاستلام', 'en': 'On delivery'},
  'pay_pending': {'ar': 'بانتظار الدفع', 'en': 'Awaiting payment'},
  'pay_paid': {'ar': 'مدفوع', 'en': 'Paid'},
  'pay_failed': {'ar': 'فشل الدفع', 'en': 'Payment failed'},
  'pay_refunded': {'ar': 'مسترد', 'en': 'Refunded'},
};

String tr(String key, String lang) => _s[key]?[lang] ?? _s[key]?['ar'] ?? key;

String money(double v) {
  final whole = v == v.roundToDouble();
  final s = whole ? v.toStringAsFixed(0) : v.toStringAsFixed(2);
  final parts = s.split('.');
  final buf = StringBuffer();
  for (var i = 0; i < parts[0].length; i++) {
    if (i > 0 && (parts[0].length - i) % 3 == 0) buf.write(',');
    buf.write(parts[0][i]);
  }
  return parts.length > 1 ? '$buf.${parts[1]}' : buf.toString();
}
