import 'package:flutter/foundation.dart' hide Category;
import '../models/product.dart';
import '../models/category.dart';
import '../services/api_service.dart';
import '../config/app_config.dart';

class ProductsProvider with ChangeNotifier {
  List<Product> _products = [];
  List<Product> _featuredProducts = [];
  List<Category> _categories = [];
  Product? _selectedProduct;

  bool _isLoading = false;
  String? _error;
  int _currentPage = 1;
  bool _hasMore = true;
  int? _selectedCategoryId;
  String _searchQuery = '';
  String _sortBy = 'newest';

  // --- GETTERS ---
  List<Product> get products => _products;
  List<Product> get featuredProducts => _featuredProducts;
  List<Category> get categories => _categories;
  Product? get selectedProduct => _selectedProduct;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get hasMore => _hasMore;
  int? get selectedCategoryId => _selectedCategoryId;

  final ApiService _apiService = ApiService();

  // --- DATA FETCHING METHODS ---

  Future<void> fetchProducts({bool refresh = false}) async {
    if (_isLoading && !refresh) return;

    if (refresh) {
      _currentPage = 1;
      _hasMore = true;
      _products = [];
    }

    if (!_hasMore) return;

    _isLoading = true;
    _error = null;
    if (refresh) notifyListeners();

    try {
      // FIX: Added 'action=list' and changed 'limit' to 'per_page' to match PHP
      String endpoint = 'products.php?action=list&page=$_currentPage&per_page=${AppConfig.productsPerPage}&sort=$_sortBy';

      if (_selectedCategoryId != null) endpoint += '&category_id=$_selectedCategoryId';
      if (_searchQuery.isNotEmpty) endpoint += '&search=$_searchQuery';

      final response = await _apiService.get(endpoint);

      if (response['success'] == true && response['data'] is List) {
        final List<Product> newProducts = (response['data'] as List)
            .map((p) => Product.fromJson(p as Map<String, dynamic>))
            .toList();

        if (refresh) {
          _products = newProducts;
        } else {
          _products.addAll(newProducts);
        }

        // التحقق من المزيد من البيانات
        // إذا كان عدد المنتجات الراجعة أقل من الحد المطلوب، فهذا يعني أنها الصفحة الأخيرة
        _hasMore = newProducts.length >= AppConfig.productsPerPage;
        if (_hasMore) _currentPage++;

      } else {
        _error = response['message'] ?? 'فشل جلب المنتجات';
        _hasMore = false;
      }
    } catch (e) {
      _error = 'حدث خطأ أثناء جلب المنتجات: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchFeaturedProducts() async {
    try {
      // FIX: Changed to 'action=featured'
      final response = await _apiService.get('products.php?action=featured&limit=10');

      if (response['success'] == true && response['data'] is List) {
        _featuredProducts = (response['data'] as List)
            .map((p) => Product.fromJson(p as Map<String, dynamic>))
            .toList();
        notifyListeners();
      }
    } catch (_) {
      // Silent error for featured
    }
  }

  Future<void> fetchCategories() async {
    try {
      // FIX: Explicitly added 'action=list' for clarity
      final response = await _apiService.get('categories.php?action=list');

      if (response['success'] == true && response['data'] is List) {
        _categories = (response['data'] as List)
            .map((c) => Category.fromJson(c as Map<String, dynamic>))
            .toList();
        notifyListeners();
      }
    } catch (_) {
      // Silent error
    }
  }

  Future<void> fetchProductDetails(int productId) async {
    _isLoading = true;
    _selectedProduct = null;
    _error = null;
    notifyListeners();

    try {
      // FIX: Added 'action=get'
      final response = await _apiService.get('products.php?action=get&id=$productId');

      if (response['success'] == true && response['data'] != null) {
        _selectedProduct = Product.fromJson(response['data']);
      } else {
        _error = response['message'] ?? 'فشل جلب تفاصيل المنتج';
      }
    } catch (e) {
      _error = 'حدث خطأ أثناء جلب تفاصيل المنتج: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // --- ACTION METHODS ---

  void setCategory(int? categoryId) {
    if (_selectedCategoryId == categoryId) return;
    _selectedCategoryId = categoryId;
    // عند تغيير التصنيف، يجب تصفير البحث
    _searchQuery = '';
    fetchProducts(refresh: true);
  }

  void setSearchQuery(String query) {
    if (_searchQuery == query) return;
    _searchQuery = query;
    // عند البحث، قد ترغب في إلغاء تحديد التصنيف للبحث في الكل، أو إبقائه للبحث داخل التصنيف
    // سنبقيه كما هو للبحث المخصص
    fetchProducts(refresh: true);
  }

  void setSortBy(String sort) {
    if (_sortBy == sort) return;
    _sortBy = sort;
    fetchProducts(refresh: true);
  }

  void clearFilters() {
    _selectedCategoryId = null;
    _searchQuery = '';
    _sortBy = 'newest';
    fetchProducts(refresh: true);
  }
}