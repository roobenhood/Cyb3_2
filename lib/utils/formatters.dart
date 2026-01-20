import 'package:intl/intl.dart';

class Formatters {
  static final _priceFormat = NumberFormat.currency(
    locale: 'ar_SA',
    symbol: 'ر.س',
    decimalDigits: 2,
  );

  static final _dateFormat = DateFormat('dd/MM/yyyy', 'ar');
  static final _dateTimeFormat = DateFormat('dd/MM/yyyy HH:mm', 'ar');
  static final _timeFormat = DateFormat('HH:mm', 'ar');

  /// تنسيق السعر
  static String formatPrice(double price) {
    return _priceFormat.format(price);
  }

  /// تنسيق التاريخ
  static String formatDate(DateTime date) {
    return _dateFormat.format(date);
  }

  /// تنسيق التاريخ والوقت
  static String formatDateTime(DateTime dateTime) {
    return _dateTimeFormat.format(dateTime);
  }

  /// تنسيق الوقت
  static String formatTime(DateTime time) {
    return _timeFormat.format(time);
  }

  /// تنسيق التاريخ النسبي (منذ...)
  static String formatRelativeDate(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);

    if (difference.inDays > 365) {
      final years = (difference.inDays / 365).floor();
      return 'منذ $years ${years == 1 ? 'سنة' : 'سنوات'}';
    } else if (difference.inDays > 30) {
      final months = (difference.inDays / 30).floor();
      return 'منذ $months ${months == 1 ? 'شهر' : 'أشهر'}';
    } else if (difference.inDays > 0) {
      return 'منذ ${difference.inDays} ${difference.inDays == 1 ? 'يوم' : 'أيام'}';
    } else if (difference.inHours > 0) {
      return 'منذ ${difference.inHours} ${difference.inHours == 1 ? 'ساعة' : 'ساعات'}';
    } else if (difference.inMinutes > 0) {
      return 'منذ ${difference.inMinutes} ${difference.inMinutes == 1 ? 'دقيقة' : 'دقائق'}';
    } else {
      return 'الآن';
    }
  }

  /// تنسيق رقم الهاتف
  static String formatPhone(String phone) {
    // إزالة المسافات والرموز
    phone = phone.replaceAll(RegExp(r'[^\d+]'), '');
    
    if (phone.startsWith('+966')) {
      // رقم سعودي
      if (phone.length == 13) {
        return '${phone.substring(0, 4)} ${phone.substring(4, 6)} ${phone.substring(6, 9)} ${phone.substring(9)}';
      }
    }
    
    return phone;
  }

  /// تقصير النص
  static String truncate(String text, int maxLength) {
    if (text.length <= maxLength) return text;
    return '${text.substring(0, maxLength)}...';
  }

  /// تنسيق الأرقام الكبيرة
  static String formatNumber(int number) {
    if (number >= 1000000) {
      return '${(number / 1000000).toStringAsFixed(1)}M';
    } else if (number >= 1000) {
      return '${(number / 1000).toStringAsFixed(1)}K';
    }
    return number.toString();
  }

  /// تنسيق حجم الملف
  static String formatFileSize(int bytes) {
    if (bytes >= 1073741824) {
      return '${(bytes / 1073741824).toStringAsFixed(2)} GB';
    } else if (bytes >= 1048576) {
      return '${(bytes / 1048576).toStringAsFixed(2)} MB';
    } else if (bytes >= 1024) {
      return '${(bytes / 1024).toStringAsFixed(2)} KB';
    }
    return '$bytes bytes';
  }
}
