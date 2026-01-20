class AppConfig {
  static const String appName = 'متجري';
  static const String appVersion = '1.0.0';

  static const String apiBaseUrl = 'http://192.168.8.120/fin/php/api/';

  static const String imagesBaseUrl = 'http://192.168.8.120/fin/php/uploads/'; // <-- تم تحديث هذا أيضًا
  static const String placeholderImage = 'assets/images/placeholder.png';
  

  static const int cacheMaxAge = 7;
  static const int maxCacheSize = 100;
  static const int connectionTimeout = 30000;
  static const int receiveTimeout = 30000;
  

  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String themeKey = 'theme_mode';
  static const String languageKey = 'language';
  static const String introSeenKey = 'intro_seen';
  

  static const int productsPerPage = 20;
  static const int reviewsPerPage = 10;
  

  static const int primaryColorValue = 0xFF2196F3;
  static const int secondaryColorValue = 0xFF03DAC6;
  static const int errorColorValue = 0xFFB00020;
}
