/// نموذج المنتج
class Product {
  final int? id;
  final String name;
  final String description;
  final double price;
  final double? discountPrice;
  final String imageUrl;
  final List<String> images;
  final int categoryId;
  final String? categoryName;
  final int stock;
  final double rating;
  final int reviewCount;
  final bool isFeatured;
  final bool isActive;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Product({
    this.id,
    required this.name,
    required this.description,
    required this.price,
    this.discountPrice,
    required this.imageUrl,
    this.images = const [],
    required this.categoryId,
    this.categoryName,
    this.stock = 0,
    this.rating = 0.0,
    this.reviewCount = 0,
    this.isFeatured = false,
    this.isActive = true,
    this.createdAt,
    this.updatedAt,
  });

  /// السعر الفعلي بعد الخصم
  double get effectivePrice => discountPrice ?? price;

  /// هل يوجد خصم؟
  bool get hasDiscount => discountPrice != null && discountPrice! < price;

  /// نسبة الخصم
  double get discountPercentage {
    if (!hasDiscount) return 0;
    return ((price - discountPrice!) / price * 100);
  }

  /// هل المنتج متوفر؟
  bool get isInStock => stock > 0;

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: json['id'] as int?,
      name: json['name'] as String? ?? json['title'] as String? ?? '',
      description: json['description'] as String? ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0.0,
      discountPrice: json['discount_price'] != null
          ? (json['discount_price'] as num).toDouble()
          : null,
      imageUrl: json['image_url'] ?? json['thumbnail'] ?? json['image'] as String? ?? '',
      images: json['images'] != null
          ? List<String>.from(json['images'])
          : [],
      categoryId: json['category_id'] as int? ?? 0,
      categoryName: json['category_name'] as String?,
      stock: json['stock'] as int? ?? 0,
      rating: (json['rating'] as num?)?.toDouble() ?? 0.0,
      reviewCount: json['review_count'] ?? json['reviews_count'] as int? ?? 0,
      isFeatured: json['is_featured'] == 1 || json['is_featured'] == true,
      isActive: json['is_active'] == 1 || json['is_active'] == true,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'discount_price': discountPrice,
      'image_url': imageUrl,
      'images': images,
      'category_id': categoryId,
      'category_name': categoryName,
      'stock': stock,
      'rating': rating,
      'review_count': reviewCount,
      'is_featured': isFeatured ? 1 : 0,
      'is_active': isActive ? 1 : 0,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  Map<String, dynamic> toMap() {
    return {
      if (id != null) 'id': id,
      'name': name,
      'description': description,
      'price': price,
      'discount_price': discountPrice,
      'image_url': imageUrl,
      'images': images.join(','),
      'category_id': categoryId,
      'stock': stock,
      'rating': rating,
      'review_count': reviewCount,
      'is_featured': isFeatured ? 1 : 0,
      'is_active': isActive ? 1 : 0,
    };
  }

  Product copyWith({
    int? id,
    String? name,
    String? description,
    double? price,
    double? discountPrice,
    String? imageUrl,
    List<String>? images,
    int? categoryId,
    String? categoryName,
    int? stock,
    double? rating,
    int? reviewCount,
    bool? isFeatured,
    bool? isActive,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Product(
      id: id ?? this.id,
      name: name ?? this.name,
      description: description ?? this.description,
      price: price ?? this.price,
      discountPrice: discountPrice ?? this.discountPrice,
      imageUrl: imageUrl ?? this.imageUrl,
      images: images ?? this.images,
      categoryId: categoryId ?? this.categoryId,
      categoryName: categoryName ?? this.categoryName,
      stock: stock ?? this.stock,
      rating: rating ?? this.rating,
      reviewCount: reviewCount ?? this.reviewCount,
      isFeatured: isFeatured ?? this.isFeatured,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'Product(id: $id, name: $name, price: $price)';
  }
}
