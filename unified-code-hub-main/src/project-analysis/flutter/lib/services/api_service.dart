import 'dart:convert';
import 'package:http/http.dart' as http;

/// خدمة الاتصال بـ API
/// API Service for connecting Flutter to PHP Backend
class ApiService {
  // تحديث هذا الرابط حسب السيرفر الخاص بك
  static const String baseUrl = 'http://your-server.com/api';
  
  String? _token;
  
  // Singleton pattern
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();
  
  // Set auth token
  void setToken(String? token) {
    _token = token;
  }
  
  // Get headers
  Map<String, String> get _headers {
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };
    if (_token != null) {
      headers['Authorization'] = 'Bearer $_token';
    }
    return headers;
  }
  
  // ==================== Authentication ====================
  
  /// تسجيل الدخول
  Future<ApiResponse> login(String email, String password) async {
    return _post('auth.php?action=login', {
      'email': email,
      'password': password,
    });
  }
  
  /// إنشاء حساب جديد
  Future<ApiResponse> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    return _post('auth.php?action=register', {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });
  }
  
  /// جلب الملف الشخصي
  Future<ApiResponse> getProfile() async {
    return _get('auth.php?action=profile');
  }
  
  /// تحديث الملف الشخصي
  Future<ApiResponse> updateProfile(Map<String, dynamic> data) async {
    return _post('auth.php?action=update-profile', data);
  }
  
  /// تغيير كلمة المرور
  Future<ApiResponse> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    return _post('auth.php?action=change-password', {
      'current_password': currentPassword,
      'new_password': newPassword,
      'new_password_confirmation': confirmPassword,
    });
  }
  
  // ==================== Products/Courses ====================
  
  /// جلب المنتجات/الدورات
  Future<ApiResponse> getProducts({
    int page = 1,
    String? categoryId,
    String? search,
    String? sort,
  }) async {
    final params = <String, String>{
      'action': 'list',
      'page': page.toString(),
    };
    if (categoryId != null) params['category_id'] = categoryId;
    if (search != null) params['search'] = search;
    if (sort != null) params['sort'] = sort;
    
    return _get('products.php?${Uri(queryParameters: params).query}');
  }
  
  /// جلب منتج/دورة واحدة
  Future<ApiResponse> getProduct(int id) async {
    return _get('products.php?action=get&id=$id');
  }
  
  /// جلب المنتجات المميزة
  Future<ApiResponse> getFeaturedProducts({int limit = 6}) async {
    return _get('products.php?action=featured&limit=$limit');
  }
  
  // ==================== Categories ====================
  
  /// جلب التصنيفات
  Future<ApiResponse> getCategories() async {
    return _get('categories.php?action=list');
  }
  
  /// جلب تصنيف واحد
  Future<ApiResponse> getCategory(int id) async {
    return _get('categories.php?action=get&id=$id');
  }
  
  // ==================== Cart ====================
  
  /// جلب السلة
  Future<ApiResponse> getCart() async {
    return _get('cart.php?action=list');
  }
  
  /// إضافة للسلة
  Future<ApiResponse> addToCart(int productId, {int quantity = 1}) async {
    return _post('cart.php?action=add', {
      'product_id': productId,
      'quantity': quantity,
    });
  }
  
  /// تحديث كمية في السلة
  Future<ApiResponse> updateCartItem(int itemId, int quantity) async {
    return _post('cart.php?action=update', {
      'item_id': itemId,
      'quantity': quantity,
    });
  }
  
  /// حذف من السلة
  Future<ApiResponse> removeFromCart(int itemId) async {
    return _post('cart.php?action=remove', {
      'item_id': itemId,
    });
  }
  
  /// تفريغ السلة
  Future<ApiResponse> clearCart() async {
    return _post('cart.php?action=clear', {});
  }
  
  // ==================== Orders ====================
  
  /// جلب الطلبات
  Future<ApiResponse> getOrders() async {
    return _get('orders.php?action=list');
  }
  
  /// جلب طلب واحد
  Future<ApiResponse> getOrder(int id) async {
    return _get('orders.php?action=get&id=$id');
  }
  
  /// إنشاء طلب جديد
  Future<ApiResponse> createOrder({
    required int addressId,
    required String paymentMethod,
    String? notes,
  }) async {
    return _post('orders.php?action=create', {
      'address_id': addressId,
      'payment_method': paymentMethod,
      if (notes != null) 'notes': notes,
    });
  }
  
  /// إلغاء طلب
  Future<ApiResponse> cancelOrder(int orderId) async {
    return _post('orders.php?action=cancel', {
      'order_id': orderId,
    });
  }
  
  // ==================== Addresses ====================
  
  /// جلب العناوين
  Future<ApiResponse> getAddresses() async {
    return _get('addresses.php?action=list');
  }
  
  /// إضافة عنوان
  Future<ApiResponse> addAddress(Map<String, dynamic> addressData) async {
    return _post('addresses.php?action=add', addressData);
  }
  
  /// تحديث عنوان
  Future<ApiResponse> updateAddress(int id, Map<String, dynamic> addressData) async {
    return _post('addresses.php?action=update', {
      'id': id,
      ...addressData,
    });
  }
  
  /// حذف عنوان
  Future<ApiResponse> deleteAddress(int id) async {
    return _post('addresses.php?action=delete', {'id': id});
  }
  
  // ==================== Favorites ====================
  
  /// جلب المفضلة
  Future<ApiResponse> getFavorites() async {
    return _get('favorites.php?action=list');
  }
  
  /// إضافة للمفضلة
  Future<ApiResponse> addToFavorites(int productId) async {
    return _post('favorites.php?action=add', {'product_id': productId});
  }
  
  /// حذف من المفضلة
  Future<ApiResponse> removeFromFavorites(int productId) async {
    return _post('favorites.php?action=remove', {'product_id': productId});
  }
  
  // ==================== Reviews ====================
  
  /// جلب التقييمات
  Future<ApiResponse> getReviews(int productId) async {
    return _get('reviews.php?action=list&product_id=$productId');
  }
  
  /// إضافة تقييم
  Future<ApiResponse> addReview({
    required int productId,
    required int rating,
    String? comment,
  }) async {
    return _post('reviews.php?action=add', {
      'product_id': productId,
      'rating': rating,
      if (comment != null) 'comment': comment,
    });
  }
  
  // ==================== HTTP Methods ====================
  
  Future<ApiResponse> _get(String endpoint) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/$endpoint'),
        headers: _headers,
      );
      return _handleResponse(response);
    } catch (e) {
      return ApiResponse(
        success: false,
        message: 'فشل الاتصال بالخادم: ${e.toString()}',
      );
    }
  }
  
  Future<ApiResponse> _post(String endpoint, Map<String, dynamic> data) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/$endpoint'),
        headers: _headers,
        body: jsonEncode(data),
      );
      return _handleResponse(response);
    } catch (e) {
      return ApiResponse(
        success: false,
        message: 'فشل الاتصال بالخادم: ${e.toString()}',
      );
    }
  }
  
  ApiResponse _handleResponse(http.Response response) {
    try {
      final data = jsonDecode(response.body);
      return ApiResponse(
        success: data['success'] ?? false,
        message: data['message'] ?? '',
        data: data['data'],
        errors: data['errors'] != null 
            ? Map<String, String>.from(data['errors']) 
            : null,
      );
    } catch (e) {
      return ApiResponse(
        success: false,
        message: 'خطأ في معالجة الاستجابة',
      );
    }
  }
}

/// نموذج استجابة API
class ApiResponse {
  final bool success;
  final String message;
  final dynamic data;
  final Map<String, String>? errors;
  
  ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.errors,
  });
}
