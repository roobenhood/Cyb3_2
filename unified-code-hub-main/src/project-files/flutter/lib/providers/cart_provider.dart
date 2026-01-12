import 'package:flutter/material.dart';
import '../models/course.dart';
import '../services/database_helper.dart';

class CartProvider extends ChangeNotifier {
  List<Course> _items = [];
  bool _isLoading = false;

  List<Course> get items => _items;
  bool get isLoading => _isLoading;
  int get itemCount => _items.length;

  double get subtotal => _items.fold(0, (sum, item) => sum + item.effectivePrice);
  double get shipping => subtotal > 500 ? 0 : 25;
  double get tax => subtotal * 0.15;
  double get total => subtotal + shipping + tax;

  Future<void> loadCart(int userId) async {
    _isLoading = true;
    notifyListeners();

    _items = await DatabaseHelper.instance.getCartCourses(userId);

    _isLoading = false;
    notifyListeners();
  }

  Future<bool> addToCart(int userId, Course course) async {
    try {
      await DatabaseHelper.instance.addToCart(userId, course.id!);
      _items.add(course);
      notifyListeners();
      return true;
    } catch (e) {
      return false;
    }
  }

  Future<void> removeFromCart(int userId, int courseId) async {
    await DatabaseHelper.instance.removeFromCart(userId, courseId);
    _items.removeWhere((item) => item.id == courseId);
    notifyListeners();
  }

  Future<void> clearCart(int userId) async {
    await DatabaseHelper.instance.clearCart(userId);
    _items.clear();
    notifyListeners();
  }

  Future<bool> isInCart(int userId, int courseId) async {
    return await DatabaseHelper.instance.isInCart(userId, courseId);
  }

  void clear() {
    _items.clear();
    notifyListeners();
  }
}
