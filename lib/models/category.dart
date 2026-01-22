class Category {
  final int id;
  final String name;
  final String? description;
  final String? image;
  final int? parentId;
  final int productsCount;
  final bool isActive;
  final DateTime createdAt;

  Category({
    required this.id,
    required this.name,
    this.description,
    this.image,
    this.parentId,
    this.productsCount = 0,
    this.isActive = true,
    required this.createdAt,
  });

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      description: json['description'],
      image: json['image'],
      parentId: json['parent_id'],
      productsCount: json['products_count'] ?? 0,
      isActive: json['is_active'] == 1 || json['is_active'] == true,
      createdAt: json['created_at'] != null 
          ? DateTime.parse(json['created_at']) 
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'image': image,
      'parent_id': parentId,
      'products_count': productsCount,
      'is_active': isActive,
      'created_at': createdAt.toIso8601String(),
    };
  }
}
