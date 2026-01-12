import 'package:intl/intl.dart';
import '../config/app_config.dart';

/// تنسيق الأرقام والتواريخ
class Formatters {
  /// تنسيق السعر
  static String formatPrice(double price) {
    final formatter = NumberFormat.currency(
      locale: 'ar_SA',
      symbol: AppConfig.currency,
      decimalDigits: 2,
    );
    return formatter.format(price);
  }

  /// تنسيق السعر بدون عملة
  static String formatNumber(double number) {
    final formatter = NumberFormat('#,##0.00', 'ar');
    return formatter.format(number);
  }

  /// تنسيق التاريخ
  static String formatDate(DateTime date) {
    return DateFormat('yyyy/MM/dd', 'ar').format(date);
  }

  /// تنسيق التاريخ والوقت
  static String formatDateTime(DateTime date) {
    return DateFormat('yyyy/MM/dd HH:mm', 'ar').format(date);
  }

  /// تنسيق الوقت النسبي
  static String formatRelativeTime(DateTime date) {
    final now = DateTime.now();
    final difference = now.difference(date);

    if (difference.inDays > 365) {
      return 'منذ ${(difference.inDays / 365).floor()} سنة';
    } else if (difference.inDays > 30) {
      return 'منذ ${(difference.inDays / 30).floor()} شهر';
    } else if (difference.inDays > 7) {
      return 'منذ ${(difference.inDays / 7).floor()} أسبوع';
    } else if (difference.inDays > 0) {
      return 'منذ ${difference.inDays} يوم';
    } else if (difference.inHours > 0) {
      return 'منذ ${difference.inHours} ساعة';
    } else if (difference.inMinutes > 0) {
      return 'منذ ${difference.inMinutes} دقيقة';
    } else {
      return 'الآن';
    }
  }

  /// تنسيق نسبة الخصم
  static String formatDiscount(double percentage) {
    return '${percentage.toStringAsFixed(0)}% خصم';
  }

  /// تنسيق رقم الهاتف
  static String formatPhone(String phone) {
    // تنظيف الرقم
    final cleaned = phone.replaceAll(RegExp(r'\D'), '');
    
    if (cleaned.length == 10) {
      return '${cleaned.substring(0, 3)} ${cleaned.substring(3, 6)} ${cleaned.substring(6)}';
    }
    
    return phone;
  }

  /// اختصار النص
  static String truncate(String text, int maxLength) {
    if (text.length <= maxLength) return text;
    return '${text.substring(0, maxLength)}...';
  }
}
