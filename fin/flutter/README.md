# مشروع المتجر الإلكتروني - Flutter + Web + PHP

## الهيكل الكامل

```
project/
├── flutter/                    # تطبيق Flutter للموبايل
│   ├── lib/
│   │   ├── main.dart
│   │   ├── config/
│   │   │   └── app_config.dart
│   │   ├── models/
│   │   │   ├── user.dart
│   │   │   ├── product.dart
│   │   │   ├── category.dart
│   │   │   ├── cart_item.dart
│   │   │   ├── order.dart
│   │   │   └── review.dart
│   │   ├── screens/
│   │   │   ├── splash_screen.dart
│   │   │   ├── introduction_screen.dart
│   │   │   ├── login_screen.dart
│   │   │   ├── register_screen.dart
│   │   │   ├── home_screen.dart
│   │   │   ├── products_screen.dart
│   │   │   ├── product_detail_screen.dart
│   │   │   ├── cart_screen.dart
│   │   │   ├── checkout_screen.dart
│   │   │   ├── orders_screen.dart
│   │   │   ├── favorites_screen.dart
│   │   │   ├── profile_screen.dart
│   │   │   └── settings_screen.dart
│   │   ├── services/
│   │   │   ├── api_service.dart
│   │   │   ├── database_helper.dart
│   │   │   └── firebase_auth_service.dart
│   │   ├── providers/
│   │   │   ├── auth_provider.dart
│   │   │   ├── products_provider.dart
│   │   │   ├── cart_provider.dart
│   │   │   ├── favorites_provider.dart
│   │   │   └── theme_provider.dart
│   │   ├── widgets/
│   │   │   ├── product_card.dart
│   │   │   └── category_chip.dart
│   │   └── utils/
│   │       ├── validators.dart
│   │       ├── formatters.dart
│   │       └── permission_handler.dart
│   └── pubspec.yaml
│
└── web/                        # (يتم نسخه من المشروع الأصلي)
```

## ملاحظات مهمة

1. **هذا متجر إلكتروني** وليس منصة كورسات
2. تم إصلاح جميع المسميات لتتوافق مع المتجر
3. تم إضافة `api_service.dart` للاتصال بـ PHP Backend
4. جميع الملفات متوافقة ومترابطة

## التشغيل

```bash
cd project/flutter
flutter pub get
flutter run
```
