/**
 * Application Configuration
 * إعدادات التطبيق
 */

const CONFIG = {
    // API Base URL - Update this to your server URL
    API_URL: 'http://localhost/courses-platform/api',
    
    // Storage Keys
    TOKEN_KEY: 'auth_token',
    USER_KEY: 'user_data',
    CART_KEY: 'cart_items',
    
    // Default Values
    DEFAULT_PAGE_SIZE: 10,
    DEFAULT_THUMBNAIL: 'images/default-course.jpg',
    DEFAULT_AVATAR: 'images/default-avatar.png',
    
    // Currency
    CURRENCY: '$',
    
    // Levels Translation
    LEVELS: {
        beginner: 'مبتدئ',
        intermediate: 'متوسط',
        advanced: 'متقدم'
    },
    
    // Messages
    MESSAGES: {
        LOGIN_SUCCESS: 'تم تسجيل الدخول بنجاح',
        LOGIN_ERROR: 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
        REGISTER_SUCCESS: 'تم إنشاء الحساب بنجاح',
        LOGOUT_SUCCESS: 'تم تسجيل الخروج بنجاح',
        CART_ADD_SUCCESS: 'تمت إضافة الدورة إلى السلة',
        CART_REMOVE_SUCCESS: 'تمت إزالة الدورة من السلة',
        ENROLL_SUCCESS: 'تم التسجيل في الدورة بنجاح',
        REVIEW_SUCCESS: 'تم إضافة التقييم بنجاح',
        ERROR_GENERAL: 'حدث خطأ، يرجى المحاولة مرة أخرى',
        REQUIRED_FIELD: 'هذا الحقل مطلوب',
        INVALID_EMAIL: 'البريد الإلكتروني غير صحيح',
        PASSWORD_MIN: 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
        PASSWORD_MISMATCH: 'كلمة المرور غير متطابقة'
    }
};

// Freeze config to prevent modifications
Object.freeze(CONFIG);
