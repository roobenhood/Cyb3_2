import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/database_helper.dart';
import '../services/api_service.dart';

/// مزود حالة المفضلة
class FavoritesProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<Product> _favorites = [];
  Set<int> _favoriteIds = {};
  bool _isLoading = false;
  String? _error;
  int? _userId;

  List<Product> get favorites => _favorites;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int get count => _favorites.length;

  /// تعيين معرف المستخدم
  void setUserId(int userId) {
    _userId = userId;
    loadFavorites();
  }

  /// تحميل المفضلة
  Future<void> loadFavorites() async {
    if (_userId == null) return;

    _isLoading = true;
    notifyListeners();

    try {
      _favorites = await DatabaseHelper.instance.getFavorites(_userId!);
      _favoriteIds = _favorites.map((p) => p.id!).toSet();
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// هل المنتج في المفضلة؟
  bool isFavorite(int productId) {
    return _favoriteIds.contains(productId);
  }

  /// تبديل حالة المفضلة
  Future<void> toggleFavorite(Product product) async {
    if (_userId == null || product.id == null) return;

    try {
      if (isFavorite(product.id!)) {
        await removeFromFavorites(product.id!);
      } else {
        await addToFavorites(product);
      }
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// إضافة للمفضلة
  Future<void> addToFavorites(Product product) async {
    if (_userId == null || product.id == null) return;

    try {
      await DatabaseHelper.instance.addToFavorites(_userId!, product.id!);
      _favorites.add(product);
      _favoriteIds.add(product.id!);

      // مزامنة مع API
      await _apiService.addToFavorites(product.id!);

      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// إزالة من المفضلة
  Future<void> removeFromFavorites(int productId) async {
    if (_userId == null) return;

    try {
      await DatabaseHelper.instance.removeFromFavorites(_userId!, productId);
      _favorites.removeWhere((p) => p.id == productId);
      _favoriteIds.remove(productId);

      // مزامنة مع API
      await _apiService.removeFromFavorites(productId);

      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
