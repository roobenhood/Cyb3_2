/// التحقق من صحة المدخلات
class Validators {
  /// التحقق من البريد الإلكتروني
  static String? validateEmail(String? value) {
    if (value == null || value.isEmpty) {
      return 'البريد الإلكتروني مطلوب';
    }
    
    final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    if (!emailRegex.hasMatch(value)) {
      return 'البريد الإلكتروني غير صالح';
    }
    
    return null;
  }

  /// التحقق من كلمة المرور
  static String? validatePassword(String? value) {
    if (value == null || value.isEmpty) {
      return 'كلمة المرور مطلوبة';
    }
    
    if (value.length < 6) {
      return 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    }
    
    return null;
  }

  /// التحقق من تأكيد كلمة المرور
  static String? validateConfirmPassword(String? value, String password) {
    if (value == null || value.isEmpty) {
      return 'تأكيد كلمة المرور مطلوب';
    }
    
    if (value != password) {
      return 'كلمة المرور غير متطابقة';
    }
    
    return null;
  }

  /// التحقق من الاسم
  static String? validateName(String? value) {
    if (value == null || value.isEmpty) {
      return 'الاسم مطلوب';
    }
    
    if (value.length < 2) {
      return 'الاسم يجب أن يكون حرفين على الأقل';
    }
    
    if (value.length > 100) {
      return 'الاسم طويل جداً';
    }
    
    return null;
  }

  /// التحقق من رقم الهاتف
  static String? validatePhone(String? value) {
    if (value == null || value.isEmpty) {
      return null; // اختياري
    }
    
    final phoneRegex = RegExp(r'^[+]?[\d\s-]{8,15}$');
    if (!phoneRegex.hasMatch(value)) {
      return 'رقم الهاتف غير صالح';
    }
    
    return null;
  }

  /// التحقق من حقل مطلوب
  static String? validateRequired(String? value, {String fieldName = 'هذا الحقل'}) {
    if (value == null || value.isEmpty) {
      return '$fieldName مطلوب';
    }
    return null;
  }

  /// التحقق من الحد الأدنى للطول
  static String? validateMinLength(String? value, int minLength, {String fieldName = 'هذا الحقل'}) {
    if (value == null || value.isEmpty) {
      return '$fieldName مطلوب';
    }
    
    if (value.length < minLength) {
      return '$fieldName يجب أن يكون $minLength أحرف على الأقل';
    }
    
    return null;
  }

  /// التحقق من الحد الأقصى للطول
  static String? validateMaxLength(String? value, int maxLength, {String fieldName = 'هذا الحقل'}) {
    if (value == null || value.isEmpty) {
      return null;
    }
    
    if (value.length > maxLength) {
      return '$fieldName يجب أن لا يتجاوز $maxLength حرف';
    }
    
    return null;
  }

  /// التحقق من رقم
  static String? validateNumber(String? value, {String fieldName = 'هذا الحقل'}) {
    if (value == null || value.isEmpty) {
      return '$fieldName مطلوب';
    }
    
    if (double.tryParse(value) == null) {
      return '$fieldName يجب أن يكون رقماً';
    }
    
    return null;
  }

  /// التحقق من رقم موجب
  static String? validatePositiveNumber(String? value, {String fieldName = 'هذا الحقل'}) {
    final numberError = validateNumber(value, fieldName: fieldName);
    if (numberError != null) return numberError;
    
    final number = double.parse(value!);
    if (number <= 0) {
      return '$fieldName يجب أن يكون رقماً موجباً';
    }
    
    return null;
  }

  /// التحقق من URL
  static String? validateUrl(String? value) {
    if (value == null || value.isEmpty) {
      return null; // اختياري
    }
    
    try {
      final uri = Uri.parse(value);
      if (!uri.hasScheme || !uri.hasAuthority) {
        return 'الرابط غير صالح';
      }
    } catch (e) {
      return 'الرابط غير صالح';
    }
    
    return null;
  }

  /// تنظيف النص من XSS
  static String sanitize(String input) {
    return input
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#x27;')
        .replaceAll('/', '&#x2F;');
  }
}
