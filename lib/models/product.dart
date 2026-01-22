import 'dart:convert';

// Helper functions for safe JSON parsing
double _toDouble(dynamic value, [double defaultValue = 0.0]) {
  if (value == null) return defaultValue;
  if (value is double) return value;
  if (value is int) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? defaultValue;
  return defaultValue;
}

int _toInt(dynamic value, [int defaultValue = 0]) {
  if (value == null) return defaultValue;
  if (value is int) return value;
  if (value is double) return value.toInt();
  if (value is String) return int.tryParse(value) ?? defaultValue;
  return defaultValue;
}

class Product {
  final int id;
  final String name;
  final String? description;
  final double price;
  final double? discountPrice;
  final int stock;
  final int categoryId;
  final String? categoryName;
  final String? image;
  final List<String> images;
  final double rating;
  final int reviewCount;
  final bool isActive;
  final DateTime createdAt;

  Product({
    required this.id,
    required this.name,
    this.description,
    required this.price,
    this.discountPrice,
    required this.stock,
    required this.categoryId,
    this.categoryName,
    this.image,
    this.images = const [],
    this.rating = 0.0,
    this.reviewCount = 0,
    this.isActive = true,
    required this.createdAt,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    List<String> parsedImages = [];
    if (json['images'] != null) {
      if (json['images'] is List) {
        parsedImages = List<String>.from(json['images'].map((item) => item.toString()));
      } else if (json['images'] is String && json['images'].isNotEmpty) {
        try {
          final decoded = jsonDecode(json['images']);
          if (decoded is List) {
            parsedImages = List<String>.from(decoded.map((item) => item.toString()));
          }
        } catch (e) {
          parsedImages = [json['images']];
        }
      }
    }

    return Product(
      id: _toInt(json['id']),
      name: json['name']?.toString() ?? '',
      description: json['description']?.toString(),
      price: _toDouble(json['price']),
      discountPrice: json['discount_price'] == null ? null : _toDouble(json['discount_price']),
      stock: _toInt(json['stock']),
      categoryId: _toInt(json['category_id']),
      categoryName: json['category_name']?.toString(),
      image: json['image']?.toString(),
      images: parsedImages,
      rating: _toDouble(json['rating']),
      reviewCount: _toInt(json['review_count']),
      isActive: json['is_active'] == 1 || json['is_active'] == true,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }
  
  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'description': description,
        'price': price,
        'discount_price': discountPrice,
        'stock': stock,
        'category_id': categoryId,
        'category_name': categoryName,
        'image': image,
        'images': images,
        'rating': rating,
        'review_count': reviewCount,
        'is_active': isActive,
        'created_at': createdAt.toIso8601String(),
      };

  // Helper getters
  double get finalPrice => discountPrice ?? price;
  bool get hasDiscount => discountPrice != null && discountPrice! < price;
  double get discountPercentage {
    if (!hasDiscount) return 0.0;
    return ((price - discountPrice!) / price) * 100;
  }
  bool get inStock => stock > 0;
}
