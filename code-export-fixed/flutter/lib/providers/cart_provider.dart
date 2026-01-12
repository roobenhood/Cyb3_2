import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/database_helper.dart';

class CartProvider extends ChangeNotifier {
  List<CartItem> _items = [];
  bool _isLoading = false;

  List<CartItem> get items => _items;
  bool get isLoading => _isLoading;
  int get itemCount => _items.fold(0, (sum, item) => sum + item.quantity);
  int get uniqueItemCount => _items.length;
  
  double get subtotal => _items.fold(0, (sum, item) => sum + item.total);
  double get shipping => subtotal > 500 ? 0 : 25; // شحن مجاني للطلبات فوق 500
  double get tax => subtotal * 0.15; // ضريبة 15%
  double get total => subtotal + shipping + tax;

  Future<void> loadCart(int userId) async {
    _isLoading = true;
    notifyListeners();
    
    _items = await DatabaseHelper.instance.getCartItems(userId);
    
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> addToCart(int userId, Product product, {int quantity = 1}) async {
    try {
      await DatabaseHelper.instance.addToCart(userId, product.id!, quantity: quantity);
      await loadCart(userId);
      return true;
    } catch (e) {
      return false;
    }
  }

  Future<void> updateQuantity(int userId, int productId, int quantity) async {
    await DatabaseHelper.instance.updateCartQuantity(userId, productId, quantity);
    await loadCart(userId);
  }

  Future<void> removeFromCart(int userId, int productId) async {
    await DatabaseHelper.instance.removeFromCart(userId, productId);
    _items.removeWhere((item) => item.productId == productId);
    notifyListeners();
  }

  Future<void> clearCart(int userId) async {
    await DatabaseHelper.instance.clearCart(userId);
    _items.clear();
    notifyListeners();
  }

  Future<bool> isInCart(int userId, int productId) async {
    return await DatabaseHelper.instance.isInCart(userId, productId);
  }

  void clear() {
    _items.clear();
    notifyListeners();
  }
}
