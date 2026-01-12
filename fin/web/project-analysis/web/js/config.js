/**
 * Application Configuration (Simplified)
 * إعدادات التطبيق - مبسطة للاستخدام مع PHP Backend
 * 
 * معظم المنطق الآن في PHP
 */

const CONFIG = {
    // API Base URL - يجب تحديثه حسب السيرفر
    API_URL: '/api',

    // Storage Keys
    TOKEN_KEY: 'auth_token',
    USER_KEY: 'user_data',

    // Default Values
    DEFAULT_THUMBNAIL: 'images/default-product.jpg',
    DEFAULT_AVATAR: 'images/default-avatar.png',

    // Currency
    CURRENCY: 'ر.س',

    // Messages
    MESSAGES: {
        LOGIN_SUCCESS: 'تم تسجيل الدخول بنجاح',
        LOGIN_ERROR: 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
        REGISTER_SUCCESS: 'تم إنشاء الحساب بنجاح',
        LOGOUT_SUCCESS: 'تم تسجيل الخروج بنجاح',
        CART_ADD_SUCCESS: 'تمت إضافة المنتج إلى السلة',
        CART_REMOVE_SUCCESS: 'تمت إزالة المنتج من السلة',
        ORDER_SUCCESS: 'تم إنشاء الطلب بنجاح',
        ERROR_GENERAL: 'حدث خطأ، يرجى المحاولة مرة أخرى',
        REQUIRED_FIELD: 'هذا الحقل مطلوب',
        INVALID_EMAIL: 'البريد الإلكتروني غير صحيح',
        PASSWORD_MIN: 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
        PASSWORD_MISMATCH: 'كلمة المرور غير متطابقة'
    }
};

// Freeze config to prevent modifications
Object.freeze(CONFIG);
