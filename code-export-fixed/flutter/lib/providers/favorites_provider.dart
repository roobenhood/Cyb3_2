import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/database_helper.dart';

class FavoritesProvider extends ChangeNotifier {
  List<Product> _favorites = [];
  bool _isLoading = false;
  Set<int> _favoriteIds = {};

  List<Product> get favorites => _favorites;
  bool get isLoading => _isLoading;
  int get count => _favorites.length;

  Future<void> loadFavorites(int userId) async {
    _isLoading = true;
    notifyListeners();
    
    _favorites = await DatabaseHelper.instance.getFavorites(userId);
    _favoriteIds = _favorites.map((p) => p.id!).toSet();
    
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> toggleFavorite(int userId, Product product) async {
    final productId = product.id!;
    
    if (_favoriteIds.contains(productId)) {
      await DatabaseHelper.instance.removeFromFavorites(userId, productId);
      _favoriteIds.remove(productId);
      _favorites.removeWhere((p) => p.id == productId);
    } else {
      await DatabaseHelper.instance.addToFavorites(userId, productId);
      _favoriteIds.add(productId);
      _favorites.add(product);
    }
    
    notifyListeners();
    return _favoriteIds.contains(productId);
  }

  bool isFavorite(int productId) {
    return _favoriteIds.contains(productId);
  }

  Future<void> removeFromFavorites(int userId, int productId) async {
    await DatabaseHelper.instance.removeFromFavorites(userId, productId);
    _favoriteIds.remove(productId);
    _favorites.removeWhere((p) => p.id == productId);
    notifyListeners();
  }

  void clear() {
    _favorites.clear();
    _favoriteIds.clear();
    notifyListeners();
  }
}
