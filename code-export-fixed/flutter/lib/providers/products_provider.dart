import 'package:flutter/material.dart';
import '../models/product.dart';
import '../services/database_helper.dart';

class ProductsProvider extends ChangeNotifier {
  List<Product> _products = [];
  List<Product> _featuredProducts = [];
  List<Category> _categories = [];
  Product? _selectedProduct;
  bool _isLoading = false;
  String? _error;

  // فلاتر
  int? _selectedCategoryId;
  String? _searchQuery;
  String _sortBy = 'newest';

  List<Product> get products => _products;
  List<Product> get featuredProducts => _featuredProducts;
  List<Category> get categories => _categories;
  Product? get selectedProduct => _selectedProduct;
  bool get isLoading => _isLoading;
  String? get error => _error;

  int? get selectedCategoryId => _selectedCategoryId;
  String? get searchQuery => _searchQuery;
  String get sortBy => _sortBy;

  Future<void> loadCategories() async {
    try {
      _categories = await DatabaseHelper.instance.getCategories();
      notifyListeners();
    } catch (e) {
      _error = 'فشل تحميل الفئات';
      notifyListeners();
    }
  }

  Future<void> loadProducts() async {
    _isLoading = true;
    notifyListeners();

    try {
      _products = await DatabaseHelper.instance.getProducts(
        categoryId: _selectedCategoryId,
        search: _searchQuery,
        sortBy: _sortBy,
      );
      _error = null;
    } catch (e) {
      _error = 'فشل تحميل المنتجات';
    }

    _isLoading = false;
    notifyListeners();
  }

  Future<void> loadFeaturedProducts() async {
    try {
      _featuredProducts = await DatabaseHelper.instance.getFeaturedProducts();
      notifyListeners();
    } catch (e) {
      _error = 'فشل تحميل المنتجات المميزة';
      notifyListeners();
    }
  }

  Future<void> loadProductById(int id) async {
    _isLoading = true;
    notifyListeners();

    try {
      _selectedProduct = await DatabaseHelper.instance.getProductById(id);
      _error = null;
    } catch (e) {
      _error = 'فشل تحميل المنتج';
    }

    _isLoading = false;
    notifyListeners();
  }

  void setCategory(int? categoryId) {
    _selectedCategoryId = categoryId;
    loadProducts();
  }

  void setSearch(String? query) {
    _searchQuery = query;
    loadProducts();
  }

  void setSortBy(String sort) {
    _sortBy = sort;
    loadProducts();
  }

  void clearFilters() {
    _selectedCategoryId = null;
    _searchQuery = null;
    _sortBy = 'newest';
    loadProducts();
  }

  Future<void> refresh() async {
    await Future.wait([
      loadCategories(),
      loadProducts(),
      loadFeaturedProducts(),
    ]);
  }
}
