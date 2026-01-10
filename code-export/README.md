# منصة الكورسات التعليمية - الكود الكامل

## 📁 هيكل الملفات

```
code-export/
├── flutter/                    # تطبيق Flutter للموبايل
│   ├── lib/
│   │   ├── main.dart
│   │   ├── models/
│   │   │   └── course.dart
│   │   ├── screens/
│   │   │   ├── login_screen.dart
│   │   │   ├── register_screen.dart
│   │   │   ├── home_screen.dart
│   │   │   ├── courses_screen.dart
│   │   │   ├── profile_screen.dart
│   │   │   └── cart_screen.dart
│   │   └── services/
│   │       ├── api_service.dart
│   │       └── firebase_auth_service.dart
│   └── pubspec.yaml
│
├── php/                        # Backend PHP API
│   ├── config/
│   │   ├── database.php
│   │   └── firebase.php
│   ├── middleware/
│   │   └── auth.php
│   └── api/
│       ├── login.php
│       ├── register.php
│       ├── courses.php
│       ├── course_details.php
│       ├── purchase.php
│       ├── user_courses.php
│       └── update_profile.php
│
├── sql/                        # قاعدة البيانات
│   └── database.sql
│
└── web/                        # موقع الويب
    ├── index.html
    ├── login.html
    ├── register.html
    ├── css/
    │   └── style.css
    └── js/
        └── app.js
```

## 🚀 طريقة التشغيل

### 1. قاعدة البيانات (MySQL)

```bash
# استيراد قاعدة البيانات
mysql -u root -p < sql/database.sql
```

### 2. PHP Backend

1. ضع ملفات `php/` في مجلد `htdocs` أو `www`
2. عدّل إعدادات قاعدة البيانات في `config/database.php`
3. عدّل `YOUR_FIREBASE_PROJECT_ID` في `config/firebase.php`

```bash
# تثبيت المكتبات المطلوبة
composer require firebase/php-jwt guzzlehttp/guzzle
```

### 3. موقع الويب

1. ضع ملفات `web/` في مجلد السيرفر
2. عدّل `API_BASE_URL` في `js/app.js`

### 4. تطبيق Flutter

```bash
cd flutter

# تثبيت المكتبات
flutter pub get

# إعداد Firebase
flutterfire configure

# تشغيل التطبيق
flutter run
```

**عدّل في `api_service.dart`:**
```dart
static const String baseUrl = 'https://your-domain.com/api';
```

## 🔐 إعداد Firebase

1. اذهب إلى [Firebase Console](https://console.firebase.google.com)
2. أنشئ مشروع جديد
3. فعّل Authentication > Email/Password
4. أضف تطبيق Android/iOS
5. نزّل `google-services.json` للأندرويد
6. نزّل `GoogleService-Info.plist` للـ iOS

## 📱 الصفحات المتوفرة

### Flutter
- ✅ صفحة تسجيل الدخول
- ✅ صفحة إنشاء حساب
- ✅ الصفحة الرئيسية
- ✅ صفحة الكورسات
- ✅ تفاصيل الكورس
- ✅ السلة
- ✅ الملف الشخصي

### الويب
- ✅ الصفحة الرئيسية
- ✅ تسجيل الدخول
- ✅ إنشاء حساب
- ✅ CSS متجاوب

### API
- ✅ تسجيل الدخول
- ✅ إنشاء حساب
- ✅ جلب الكورسات
- ✅ تفاصيل كورس
- ✅ شراء كورس
- ✅ كورسات المستخدم
- ✅ تحديث الملف الشخصي

## 🔑 بيانات تجريبية

```
المدير:
- email: admin@example.com
- password: password

المدرب:
- email: instructor@example.com
- password: password

الطالب:
- email: student@example.com
- password: password
```

## ⚠️ ملاحظات مهمة

1. **الأمان**: غيّر كلمات المرور الافتراضية
2. **HTTPS**: استخدم شهادة SSL في الإنتاج
3. **Firebase**: أضف قواعد الأمان المناسبة
4. **الصور**: أضف صور placeholder في مجلد `images/`

## 📞 الدعم

إذا واجهت أي مشكلة، تحقق من:
1. إعدادات قاعدة البيانات
2. روابط API
3. إعدادات Firebase
4. صلاحيات CORS
