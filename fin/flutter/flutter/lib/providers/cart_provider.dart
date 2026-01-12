import 'package:flutter/material.dart';
import '../models/cart_item.dart';
import '../models/product.dart';
import '../services/database_helper.dart';
import '../services/api_service.dart';

/// مزود حالة السلة
class CartProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<CartItem> _items = [];
  bool _isLoading = false;
  String? _error;
  int? _userId;

  List<CartItem> get items => _items;
  bool get isLoading => _isLoading;
  String? get error => _error;
  
  /// عدد العناصر في السلة
  int get itemCount => _items.fold(0, (sum, item) => sum + item.quantity);
  
  /// المجموع الفرعي
  double get subtotal => _items.fold(0, (sum, item) => sum + item.totalPrice);
  
  /// التوفير الإجمالي
  double get totalSavings => _items.fold(0, (sum, item) => sum + item.savings);
  
  /// تكلفة الشحن (يمكن تخصيصها)
  double get shippingCost => subtotal > 200 ? 0 : 20;
  
  /// المجموع الكلي
  double get total => subtotal + shippingCost;
  
  /// هل السلة فارغة؟
  bool get isEmpty => _items.isEmpty;

  /// تعيين معرف المستخدم
  void setUserId(int userId) {
    _userId = userId;
    loadCart();
  }

  /// تحميل السلة
  Future<void> loadCart() async {
    if (_userId == null) return;

    _isLoading = true;
    notifyListeners();

    try {
      _items = await DatabaseHelper.instance.getCartItems(_userId!);
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// إضافة منتج للسلة
  Future<bool> addToCart(Product product, {int quantity = 1}) async {
    if (_userId == null) return false;

    try {
      // التحقق من وجود المنتج في السلة
      final existingIndex = _items.indexWhere((item) => item.productId == product.id);
      
      if (existingIndex >= 0) {
        // تحديث الكمية
        final newQuantity = _items[existingIndex].quantity + quantity;
        await updateQuantity(product.id!, newQuantity);
      } else {
        // إضافة عنصر جديد
        await DatabaseHelper.instance.addToCart(_userId!, product.id!, quantity: quantity);
        _items.add(CartItem(
          userId: _userId!,
          productId: product.id!,
          product: product,
          quantity: quantity,
        ));
      }

      // مزامنة مع API
      await _apiService.addToCart(product.id!, quantity: quantity);

      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      notifyListeners();
      return false;
    }
  }

  /// تحديث كمية منتج
  Future<void> updateQuantity(int productId, int quantity) async {
    if (_userId == null) return;

    try {
      if (quantity <= 0) {
        await removeFromCart(productId);
        return;
      }

      await DatabaseHelper.instance.updateCartQuantity(_userId!, productId, quantity);
      
      final index = _items.indexWhere((item) => item.productId == productId);
      if (index >= 0) {
        _items[index] = _items[index].copyWith(quantity: quantity);
      }

      // مزامنة مع API
      await _apiService.updateCartItem(productId, quantity);

      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// إزالة منتج من السلة
  Future<void> removeFromCart(int productId) async {
    if (_userId == null) return;

    try {
      await DatabaseHelper.instance.removeFromCart(_userId!, productId);
      _items.removeWhere((item) => item.productId == productId);

      // مزامنة مع API
      await _apiService.removeFromCart(productId);

      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// تفريغ السلة
  Future<void> clearCart() async {
    if (_userId == null) return;

    try {
      await DatabaseHelper.instance.clearCart(_userId!);
      _items.clear();

      // مزامنة مع API
      await _apiService.clearCart();

      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// التحقق من وجود منتج في السلة
  bool isInCart(int productId) {
    return _items.any((item) => item.productId == productId);
  }

  /// الحصول على كمية منتج في السلة
  int getQuantity(int productId) {
    final item = _items.firstWhere(
      (item) => item.productId == productId,
      orElse: () => CartItem(userId: 0, productId: 0, quantity: 0),
    );
    return item.quantity;
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
