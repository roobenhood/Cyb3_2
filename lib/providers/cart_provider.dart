import 'package:flutter/foundation.dart';
import '../models/cart_item.dart';
import '../models/product.dart';
import '../services/api_service.dart';
import '../services/database_helper.dart';

class CartProvider with ChangeNotifier {
  List<CartItem> _items = [];
  bool _isLoading = false;
  String? _error;

  List<CartItem> get items => _items;
  bool get isLoading => _isLoading;
  String? get error => _error;
  
  int get itemCount => _items.fold(0, (sum, item) => sum + item.quantity);
  
  double get subtotal => _items.fold(0, (sum, item) => sum + item.total);
  
  double get shipping => subtotal > 200 ? 0 : 25; // شحن مجاني فوق 200
  
  double get tax => subtotal * 0.15; // ضريبة 15%
  
  double get total => subtotal + shipping + tax;

  final ApiService _apiService = ApiService();
  final DatabaseHelper _dbHelper = DatabaseHelper();

  bool _isAuthenticated = false;

  void setAuthState(bool isAuthenticated) {
    _isAuthenticated = isAuthenticated;
    loadCart();
  }

  Future<void> loadCart() async {
    _isLoading = true;
    notifyListeners();

    try {
      if (_isAuthenticated) {
        // جلب السلة من السيرفر
        final response = await _apiService.get('cart.php');
        if (response['success']) {
          final data = response['data'];
          _items = (data['items'] as List)
              .map((item) => CartItem.fromJson(item))
              .toList();
        }
      } else {
        // جلب السلة من قاعدة البيانات المحلية
        _items = await _dbHelper.getCartItems();
      }
    } catch (e) {
      _error = 'حدث خطأ أثناء تحميل السلة';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> addToCart(Product product, {int quantity = 1}) async {
    _error = null;

    try {
      if (_isAuthenticated) {
        final response = await _apiService.post('cart.php', {
          'product_id': product.id,
          'quantity': quantity,
        });

        if (!response['success']) {
          _error = response['message'];
          notifyListeners();
          return false;
        }
      } else {
        await _dbHelper.addToCart(product.id, product.finalPrice, quantity: quantity);
      }

      // تحديث السلة محلياً
      final existingIndex = _items.indexWhere((item) => item.productId == product.id);
      if (existingIndex >= 0) {
        _items[existingIndex] = _items[existingIndex].copyWith(
          quantity: _items[existingIndex].quantity + quantity,
        );
      } else {
        _items.add(CartItem(
          id: DateTime.now().millisecondsSinceEpoch,
          productId: product.id,
          product: product,
          quantity: quantity,
          price: product.finalPrice,
          createdAt: DateTime.now(),
        ));
      }

      notifyListeners();
      return true;
    } catch (e) {
      _error = 'حدث خطأ أثناء إضافة المنتج';
      notifyListeners();
      return false;
    }
  }

  Future<bool> updateQuantity(int productId, int quantity) async {
    _error = null;

    try {
      if (quantity <= 0) {
        return removeFromCart(productId);
      }

      if (_isAuthenticated) {
        final response = await _apiService.put('cart.php', {
          'product_id': productId,
          'quantity': quantity,
        });

        if (!response['success']) {
          _error = response['message'];
          notifyListeners();
          return false;
        }
      } else {
        await _dbHelper.updateCartQuantity(productId, quantity);
      }

      final index = _items.indexWhere((item) => item.productId == productId);
      if (index >= 0) {
        _items[index] = _items[index].copyWith(quantity: quantity);
      }

      notifyListeners();
      return true;
    } catch (e) {
      _error = 'حدث خطأ أثناء تحديث الكمية';
      notifyListeners();
      return false;
    }
  }

  Future<bool> removeFromCart(int productId) async {
    _error = null;

    try {
      if (_isAuthenticated) {
        final response = await _apiService.delete('cart.php?product_id=$productId');

        if (!response['success']) {
          _error = response['message'];
          notifyListeners();
          return false;
        }
      } else {
        await _dbHelper.removeFromCart(productId);
      }

      _items.removeWhere((item) => item.productId == productId);
      notifyListeners();
      return true;
    } catch (e) {
      _error = 'حدث خطأ أثناء حذف المنتج';
      notifyListeners();
      return false;
    }
  }

  Future<void> clearCart() async {
    try {
      if (_isAuthenticated) {
        await _apiService.delete('cart.php?clear=true');
      } else {
        await _dbHelper.clearCart();
      }
      _items = [];
      notifyListeners();
    } catch (e) {
      _error = 'حدث خطأ أثناء مسح السلة';
      notifyListeners();
    }
  }

  bool isInCart(int productId) {
    return _items.any((item) => item.productId == productId);
  }

  int getQuantity(int productId) {
    final item = _items.firstWhere(
      (item) => item.productId == productId,
      orElse: () => CartItem(
        id: 0,
        productId: 0,
        quantity: 0,
        price: 0,
        createdAt: DateTime.now(),
      ),
    );
    return item.quantity;
  }
}
