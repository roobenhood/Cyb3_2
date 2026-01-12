/// إعدادات التطبيق الأساسية
class AppConfig {
  // عنوان الـ API الأساسي
  static const String apiBaseUrl = 'https://your-domain.com/api';
  
  // اسم التطبيق
  static const String appName = 'المتجر الإلكتروني';
  
  // اسم قاعدة البيانات المحلية
  static const String dbName = 'ecommerce_store.db';
  
  // إصدار قاعدة البيانات
  static const int dbVersion = 1;
  
  // العملة الافتراضية
  static const String currency = 'ر.س';
  
  // رقم الهاتف للدعم
  static const String supportPhone = '+966500000000';
  
  // البريد الإلكتروني للدعم
  static const String supportEmail = 'support@example.com';
  
  // رابط سياسة الخصوصية
  static const String privacyPolicyUrl = 'https://example.com/privacy';
  
  // رابط الشروط والأحكام
  static const String termsUrl = 'https://example.com/terms';
  
  // الحد الأدنى لكلمة المرور
  static const int minPasswordLength = 6;
  
  // الحد الأقصى للمنتجات في السلة
  static const int maxCartItems = 50;
  
  // مدة صلاحية الجلسة (بالساعات)
  static const int sessionTimeout = 24;
  
  // إعدادات الصور
  static const int imageQuality = 80;
  static const int thumbnailSize = 200;
  static const int maxImageSize = 1024;
  
  // إعدادات التقييمات
  static const int minRating = 1;
  static const int maxRating = 5;
  
  // إعدادات البحث
  static const int searchMinLength = 2;
  static const int searchResultsLimit = 20;
  
  // إعدادات الصفحات
  static const int pageSize = 10;
}
