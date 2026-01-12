/**
 * Application Configuration
 * إعدادات التطبيق - المتجر الإلكتروني
 */

const CONFIG = {
    // API Base URL - Update this to your server URL
    API_URL: 'http://localhost/ecommerce-api/api',

    // Storage Keys
    TOKEN_KEY: 'auth_token',
    USER_KEY: 'user_data',
    CART_KEY: 'cart_items',
    FAVORITES_KEY: 'favorites_items',

    // Default Values
    DEFAULT_PAGE_SIZE: 12,
    DEFAULT_PRODUCT_IMAGE: 'images/default-product.jpg',
    DEFAULT_AVATAR: 'images/default-avatar.png',

    // Currency
    CURRENCY: 'ر.س',
    CURRENCY_CODE: 'SAR',

    // Shipping
    SHIPPING_COST: 25.00,
    FREE_SHIPPING_THRESHOLD: 500.00,
    TAX_RATE: 0.15, // 15% VAT

    // Messages
    MESSAGES: {
        LOGIN_SUCCESS: 'تم تسجيل الدخول بنجاح',
        LOGIN_ERROR: 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
        REGISTER_SUCCESS: 'تم إنشاء الحساب بنجاح',
        LOGOUT_SUCCESS: 'تم تسجيل الخروج بنجاح',
        CART_ADD_SUCCESS: 'تمت إضافة المنتج إلى السلة',
        CART_REMOVE_SUCCESS: 'تمت إزالة المنتج من السلة',
        CART_UPDATE_SUCCESS: 'تم تحديث السلة',
        FAVORITE_ADD_SUCCESS: 'تمت إضافة المنتج إلى المفضلة',
        FAVORITE_REMOVE_SUCCESS: 'تمت إزالة المنتج من المفضلة',
        ORDER_SUCCESS: 'تم إنشاء الطلب بنجاح',
        REVIEW_SUCCESS: 'تم إضافة التقييم بنجاح',
        ERROR_GENERAL: 'حدث خطأ، يرجى المحاولة مرة أخرى',
        REQUIRED_FIELD: 'هذا الحقل مطلوب',
        INVALID_EMAIL: 'البريد الإلكتروني غير صحيح',
        PASSWORD_MIN: 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
        PASSWORD_MISMATCH: 'كلمة المرور غير متطابقة',
        OUT_OF_STOCK: 'المنتج غير متوفر حالياً',
        QUANTITY_EXCEEDED: 'الكمية المطلوبة غير متوفرة'
    },

    // Order Status
    ORDER_STATUS: {
        pending: 'قيد الانتظار',
        processing: 'قيد المعالجة',
        shipped: 'تم الشحن',
        delivered: 'تم التوصيل',
        cancelled: 'ملغي'
    },

    // Payment Status
    PAYMENT_STATUS: {
        pending: 'في انتظار الدفع',
        paid: 'مدفوع',
        failed: 'فشل الدفع',
        refunded: 'مسترد'
    }
};

// Freeze config to prevent modifications
Object.freeze(CONFIG);
