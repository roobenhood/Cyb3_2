import 'package:flutter/material.dart';
import '../models/product.dart';
import '../models/category.dart';
import '../services/database_helper.dart';
import '../services/api_service.dart';

/// مزود حالة المنتجات
class ProductsProvider extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<Product> _products = [];
  List<Product> _featuredProducts = [];
  List<Category> _categories = [];
  bool _isLoading = false;
  String? _error;
  int? _selectedCategoryId;
  String _searchQuery = '';

  List<Product> get products => _products;
  List<Product> get featuredProducts => _featuredProducts;
  List<Category> get categories => _categories;
  bool get isLoading => _isLoading;
  String? get error => _error;
  int? get selectedCategoryId => _selectedCategoryId;
  String get searchQuery => _searchQuery;

  /// تحميل المنتجات
  Future<void> loadProducts({bool refresh = false}) async {
    if (_isLoading && !refresh) return;

    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      // محاولة جلب من API
      final apiProducts = await _apiService.getProducts(
        categoryId: _selectedCategoryId,
        search: _searchQuery.isEmpty ? null : _searchQuery,
      );
      
      if (apiProducts.isNotEmpty) {
        _products = apiProducts;
      } else {
        // جلب من قاعدة البيانات المحلية
        _products = await DatabaseHelper.instance.getProducts(
          categoryId: _selectedCategoryId,
          search: _searchQuery.isEmpty ? null : _searchQuery,
        );
      }

      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  /// تحميل المنتجات المميزة
  Future<void> loadFeaturedProducts() async {
    try {
      final apiProducts = await _apiService.getFeaturedProducts();
      if (apiProducts.isNotEmpty) {
        _featuredProducts = apiProducts;
      } else {
        _featuredProducts = await DatabaseHelper.instance.getFeaturedProducts();
      }
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// تحميل التصنيفات
  Future<void> loadCategories() async {
    try {
      final apiCategories = await _apiService.getCategories();
      if (apiCategories.isNotEmpty) {
        _categories = apiCategories;
      } else {
        _categories = await DatabaseHelper.instance.getCategories();
      }
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// اختيار تصنيف
  void selectCategory(int? categoryId) {
    _selectedCategoryId = categoryId;
    loadProducts(refresh: true);
  }

  /// البحث
  void search(String query) {
    _searchQuery = query;
    loadProducts(refresh: true);
  }

  /// مسح البحث
  void clearSearch() {
    _searchQuery = '';
    loadProducts(refresh: true);
  }

  /// جلب منتج بالـ ID
  Future<Product?> getProductById(int id) async {
    try {
      // البحث في القائمة المحلية أولاً
      final localProduct = _products.firstWhere(
        (p) => p.id == id,
        orElse: () => Product(id: -1, name: '', description: '', price: 0, imageUrl: '', categoryId: 0),
      );
      if (localProduct.id != -1) return localProduct;

      // جلب من API
      final apiProduct = await _apiService.getProduct(id);
      if (apiProduct != null) return apiProduct;

      // جلب من قاعدة البيانات
      return await DatabaseHelper.instance.getProductById(id);
    } catch (e) {
      return null;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
