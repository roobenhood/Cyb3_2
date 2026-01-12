/// نموذج التصنيف
class Category {
  final int? id;
  final String name;
  final String? description;
  final String? icon;
  final String? imageUrl;
  final int? parentId;
  final int productCount;
  final bool isActive;
  final DateTime? createdAt;

  Category({
    this.id,
    required this.name,
    this.description,
    this.icon,
    this.imageUrl,
    this.parentId,
    this.productCount = 0,
    this.isActive = true,
    this.createdAt,
  });

  bool get isParent => parentId == null;
  bool get hasProducts => productCount > 0;

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'] as int?,
      name: json['name'] as String? ?? '',
      description: json['description'] as String?,
      icon: json['icon'] as String?,
      imageUrl: json['image_url'] as String?,
      parentId: json['parent_id'] as int?,
      productCount: json['product_count'] ?? json['products_count'] as int? ?? 0,
      isActive: json['is_active'] == 1 || json['is_active'] == true,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'icon': icon,
      'image_url': imageUrl,
      'parent_id': parentId,
      'product_count': productCount,
      'is_active': isActive ? 1 : 0,
      'created_at': createdAt?.toIso8601String(),
    };
  }

  Map<String, dynamic> toMap() {
    return {
      if (id != null) 'id': id,
      'name': name,
      'description': description,
      'icon': icon,
      'image_url': imageUrl,
      'parent_id': parentId,
      'is_active': isActive ? 1 : 0,
    };
  }

  Category copyWith({
    int? id,
    String? name,
    String? description,
    String? icon,
    String? imageUrl,
    int? parentId,
    int? productCount,
    bool? isActive,
    DateTime? createdAt,
  }) {
    return Category(
      id: id ?? this.id,
      name: name ?? this.name,
      description: description ?? this.description,
      icon: icon ?? this.icon,
      imageUrl: imageUrl ?? this.imageUrl,
      parentId: parentId ?? this.parentId,
      productCount: productCount ?? this.productCount,
      isActive: isActive ?? this.isActive,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  @override
  String toString() {
    return 'Category(id: $id, name: $name)';
  }
}
