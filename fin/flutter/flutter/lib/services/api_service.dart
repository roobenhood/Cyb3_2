import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../models/product.dart';
import '../models/category.dart';
import '../models/order.dart';
import '../models/review.dart';
import '../models/user.dart';

/// خدمة API للاتصال بالخادم
class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  String? _authToken;

  /// الحصول على التوكن
  Future<String?> get authToken async {
    if (_authToken != null) return _authToken;
    final prefs = await SharedPreferences.getInstance();
    _authToken = prefs.getString('auth_token');
    return _authToken;
  }

  /// حفظ التوكن
  Future<void> setAuthToken(String token) async {
    _authToken = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  /// حذف التوكن
  Future<void> clearAuthToken() async {
    _authToken = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }

  /// الهيدرز الأساسية
  Future<Map<String, String>> get _headers async {
    final token = await authToken;
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  /// ==================== المصادقة ====================

  /// تسجيل الدخول
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('${AppConfig.apiBaseUrl}/auth.php?action=login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        if (data['token'] != null) {
          await setAuthToken(data['token']);
        }
        return {'success': true, 'user': data['user'], 'token': data['token']};
      }
      return {'success': false, 'message': data['message'] ?? 'فشل تسجيل الدخول'};
    } catch (e) {
      return {'success': false, 'message': 'خطأ في الاتصال: $e'};
    }
  }

  /// إنشاء حساب جديد
  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('${AppConfig.apiBaseUrl}/auth.php?action=register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'phone': phone,
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        if (data['token'] != null) {
          await setAuthToken(data['token']);
        }
        return {'success': true, 'user': data['user'], 'token': data['token']};
      }
      return {'success': false, 'message': data['message'] ?? 'فشل إنشاء الحساب'};
    } catch (e) {
      return {'success': false, 'message': 'خطأ في الاتصال: $e'};
    }
  }

  /// تسجيل الخروج
  Future<void> logout() async {
    try {
      final headers = await _headers;
      await http.post(
        Uri.parse('${AppConfig.apiBaseUrl}/auth.php?action=logout'),
        headers: headers,
      );
    } catch (e) {
      // تجاهل الخطأ
    }
    await clearAuthToken();
  }

  /// جلب الملف الشخصي
  Future<User?> getProfile() async {
    try {
      final headers = await _headers;
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/auth.php?action=profile'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return User.fromJson(data['user']);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  /// ==================== المنتجات ====================

  /// جلب المنتجات
  Future<List<Product>> getProducts({
    int? categoryId,
    String? search,
    String? sort,
    int page = 1,
    int limit = 10,
  }) async {
    try {
      final queryParams = {
        'action': 'list',
        'page': page.toString(),
        'limit': limit.toString(),
        if (categoryId != null) 'category_id': categoryId.toString(),
        if (search != null && search.isNotEmpty) 'search': search,
        if (sort != null) 'sort': sort,
      };

      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/products.php').replace(queryParameters: queryParams),
        headers: {'Accept': 'application/json'},
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return (data['products'] as List).map((e) => Product.fromJson(e)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// جلب المنتجات المميزة
  Future<List<Product>> getFeaturedProducts() async {
    try {
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/products.php?action=featured'),
        headers: {'Accept': 'application/json'},
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return (data['products'] as List).map((e) => Product.fromJson(e)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// جلب تفاصيل منتج
  Future<Product?> getProduct(int id) async {
    try {
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/products.php?action=get&id=$id'),
        headers: {'Accept': 'application/json'},
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return Product.fromJson(data['product']);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  /// ==================== التصنيفات ====================

  /// جلب التصنيفات
  Future<List<Category>> getCategories() async {
    try {
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/categories.php?action=list'),
        headers: {'Accept': 'application/json'},
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return (data['categories'] as List).map((e) => Category.fromJson(e)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// ==================== السلة ====================

  /// جلب السلة
  Future<List<Map<String, dynamic>>> getCart() async {
    try {
      final headers = await _headers;
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/cart.php?action=list'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return List<Map<String, dynamic>>.from(data['items']);
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// إضافة للسلة
  Future<bool> addToCart(int productId, {int quantity = 1}) async {
    try {
      final headers = await _headers;
      final response = await http.post(
        Uri.parse('${AppConfig.apiBaseUrl}/cart.php?action=add'),
        headers: headers,
        body: jsonEncode({'product_id': productId, 'quantity': quantity}),
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  /// تحديث كمية في السلة
  Future<bool> updateCartItem(int productId, int quantity) async {
    try {
      final headers = await _headers;
      final response = await http.put(
        Uri.parse('${AppConfig.apiBaseUrl}/cart.php?action=update'),
        headers: headers,
        body: jsonEncode({'product_id': productId, 'quantity': quantity}),
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  /// إزالة من السلة
  Future<bool> removeFromCart(int productId) async {
    try {
      final headers = await _headers;
      final response = await http.delete(
        Uri.parse('${AppConfig.apiBaseUrl}/cart.php?action=remove&product_id=$productId'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  /// تفريغ السلة
  Future<bool> clearCart() async {
    try {
      final headers = await _headers;
      final response = await http.delete(
        Uri.parse('${AppConfig.apiBaseUrl}/cart.php?action=clear'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  /// ==================== الطلبات ====================

  /// إنشاء طلب
  Future<Map<String, dynamic>> createOrder({
    required String shippingAddress,
    required String paymentMethod,
    String? notes,
  }) async {
    try {
      final headers = await _headers;
      final response = await http.post(
        Uri.parse('${AppConfig.apiBaseUrl}/orders.php?action=create'),
        headers: headers,
        body: jsonEncode({
          'shipping_address': shippingAddress,
          'payment_method': paymentMethod,
          'notes': notes,
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return {'success': true, 'order': data['order']};
      }
      return {'success': false, 'message': data['message'] ?? 'فشل إنشاء الطلب'};
    } catch (e) {
      return {'success': false, 'message': 'خطأ في الاتصال: $e'};
    }
  }

  /// جلب الطلبات
  Future<List<Order>> getOrders() async {
    try {
      final headers = await _headers;
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/orders.php?action=list'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return (data['orders'] as List).map((e) => Order.fromJson(e)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// جلب تفاصيل طلب
  Future<Order?> getOrder(int id) async {
    try {
      final headers = await _headers;
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/orders.php?action=get&id=$id'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return Order.fromJson(data['order']);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  /// إلغاء طلب
  Future<bool> cancelOrder(int orderId) async {
    try {
      final headers = await _headers;
      final response = await http.put(
        Uri.parse('${AppConfig.apiBaseUrl}/orders.php?action=cancel&id=$orderId'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  /// ==================== التقييمات ====================

  /// جلب تقييمات منتج
  Future<List<Review>> getProductReviews(int productId) async {
    try {
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/reviews.php?action=list&product_id=$productId'),
        headers: {'Accept': 'application/json'},
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return (data['reviews'] as List).map((e) => Review.fromJson(e)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// إضافة تقييم
  Future<bool> addReview({
    required int productId,
    required int rating,
    String? comment,
  }) async {
    try {
      final headers = await _headers;
      final response = await http.post(
        Uri.parse('${AppConfig.apiBaseUrl}/reviews.php?action=create'),
        headers: headers,
        body: jsonEncode({
          'product_id': productId,
          'rating': rating,
          'comment': comment,
        }),
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  /// ==================== المفضلة ====================

  /// جلب المفضلة
  Future<List<Product>> getFavorites() async {
    try {
      final headers = await _headers;
      final response = await http.get(
        Uri.parse('${AppConfig.apiBaseUrl}/favorites.php?action=list'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 && data['success'] == true) {
        return (data['products'] as List).map((e) => Product.fromJson(e)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  /// إضافة للمفضلة
  Future<bool> addToFavorites(int productId) async {
    try {
      final headers = await _headers;
      final response = await http.post(
        Uri.parse('${AppConfig.apiBaseUrl}/favorites.php?action=add'),
        headers: headers,
        body: jsonEncode({'product_id': productId}),
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }

  /// إزالة من المفضلة
  Future<bool> removeFromFavorites(int productId) async {
    try {
      final headers = await _headers;
      final response = await http.delete(
        Uri.parse('${AppConfig.apiBaseUrl}/favorites.php?action=remove&product_id=$productId'),
        headers: headers,
      );

      final data = jsonDecode(response.body);
      return response.statusCode == 200 && data['success'] == true;
    } catch (e) {
      return false;
    }
  }
}
