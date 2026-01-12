# مشروع Flutter - متجر إلكتروني متكامل

## ✅ المتطلبات المُنفذة

### 1. الشاشات (Screens)

#### شاشة البداية (Splash Screen) ✅
- تحتوي على نفس الشعار (Logo)
- أنيميشن متعددة (Scale, Rotate, Fade)
- انتقال تلقائي للشاشة التالية

#### شاشة المقدمة (Introduction Screen) ✅
- 4 صفحات تشرح وظائف التطبيق
- أنيميشن للعناصر
- تظهر مرة واحدة فقط (تُحفظ في قاعدة البيانات)
- زر تخطي

#### شاشة تسجيل الدخول (Login Screen) ✅
- تسجيل الدخول بالإيميل والباسورد
- تسجيل الدخول بواسطة Google
- تسجيل الدخول بواسطة Facebook
- Validation كامل

#### شاشة المفضلة (Favorites Screen) ✅
- عرض المنتجات المفضلة
- إضافة/إزالة من المفضلة

#### شاشات إضافية ✅
- Home Screen - الرئيسية مع المنتجات والفئات
- Product Detail Screen - تفاصيل المنتج
- Cart Screen - سلة التسوق
- Checkout Screen - إتمام الطلب
- Orders Screen - الطلبات
- Profile Screen - الملف الشخصي
- Settings Screen - الإعدادات
- Register Screen - إنشاء حساب جديد

### 2. الباك إند وقواعد البيانات ✅

#### Firebase ✅
- Firebase Authentication
- تسجيل الدخول بالإيميل
- تسجيل الدخول الاجتماعي

#### Social Media Login ✅
- Google Sign-In
- Facebook Login

#### API خارجي ✅
- ربط الإيميل والباسورد مع API الويب
- خدمة `FirebaseAuthService` للتكامل

#### قاعدة بيانات محلية (SQLite) ✅
- **لم يتم استخدام SharedPreferences كقاعدة بيانات!**
- SQLite (sqflite) لتخزين:
  - المستخدمين
  - المنتجات
  - الفئات
  - السلة
  - المفضلة
  - الطلبات
  - التقييمات
  - الإعدادات

### 3. الميزات الإضافية ✅

#### Dark Mode ✅
- دعم الوضع الفاتح
- دعم الوضع الداكن
- الوضع التلقائي (حسب النظام)
- حفظ التفضيل في قاعدة البيانات

#### Animation ✅
- أنيميشن في Splash Screen
- أنيميشن في Introduction Screen
- تأثيرات انتقالية

## 📁 هيكل الملفات

```
flutter/
├── lib/
│   ├── main.dart
│   ├── models/
│   │   ├── user.dart
│   │   └── product.dart (Product, Category, CartItem, Order)
│   ├── providers/
│   │   ├── auth_provider.dart
│   │   ├── products_provider.dart
│   │   ├── cart_provider.dart
│   │   ├── favorites_provider.dart
│   │   └── theme_provider.dart
│   ├── services/
│   │   ├── database_helper.dart (SQLite)
│   │   └── firebase_auth_service.dart
│   ├── utils/
│   │   ├── validators.dart
│   │   └── permission_handler.dart
│   ├── widgets/
│   │   ├── product_card.dart
│   │   └── category_chip.dart
│   └── screens/
│       ├── splash_screen.dart
│       ├── introduction_screen.dart
│       ├── login_screen.dart
│       ├── register_screen.dart
│       ├── home_screen.dart
│       ├── product_detail_screen.dart
│       ├── cart_screen.dart
│       ├── checkout_screen.dart
│       ├── favorites_screen.dart
│       ├── orders_screen.dart
│       ├── profile_screen.dart
│       └── settings_screen.dart
├── assets/
│   ├── images/
│   ├── icons/
│   └── fonts/
└── pubspec.yaml
```

## 🔧 إعداد Firebase

1. أنشئ مشروع في Firebase Console
2. أضف تطبيق Android/iOS
3. قم بتحميل `google-services.json` (Android) أو `GoogleService-Info.plist` (iOS)
4. فعّل Authentication واختر:
   - Email/Password
   - Google
   - Facebook

## 🔧 إعداد Google Sign-In

1. في Firebase Console > Authentication > Sign-in method
2. فعّل Google
3. أضف SHA-1 و SHA-256 في إعدادات التطبيق

## 🔧 إعداد Facebook Login

1. أنشئ تطبيق في Facebook Developers
2. أضف Facebook Login
3. أضف الإعدادات في `android/app/src/main/res/values/strings.xml`:
```xml
<string name="facebook_app_id">YOUR_APP_ID</string>
<string name="fb_login_protocol_scheme">fbYOUR_APP_ID</string>
<string name="facebook_client_token">YOUR_CLIENT_TOKEN</string>
```

## 🚀 التشغيل

```bash
# تثبيت المكتبات
flutter pub get

# تشغيل التطبيق
flutter run
```

## ⚠️ ملاحظات مهمة

1. **SharedPreferences**: يُستخدم فقط لتخزين إعدادات بسيطة، **ليس كقاعدة بيانات رئيسية**
2. **SQLite**: هي قاعدة البيانات الرئيسية لتخزين كل البيانات
3. **Firebase**: إلزامي للمصادقة
4. **API**: يتم المزامنة مع API الويب للإيميل والباسورد

## 📦 المكتبات المستخدمة

- `firebase_core` & `firebase_auth`: Firebase
- `google_sign_in`: تسجيل Google
- `flutter_facebook_auth`: تسجيل Facebook
- `sqflite`: قاعدة بيانات SQLite
- `provider`: State Management
- `cached_network_image`: تحميل الصور
- `permission_handler`: الصلاحيات
