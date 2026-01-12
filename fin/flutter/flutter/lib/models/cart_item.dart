import 'product.dart';

/// نموذج عنصر السلة
class CartItem {
  final int? id;
  final int userId;
  final int productId;
  final Product? product;
  final int quantity;
  final DateTime? createdAt;

  CartItem({
    this.id,
    required this.userId,
    required this.productId,
    this.product,
    this.quantity = 1,
    this.createdAt,
  });

  /// السعر الإجمالي للعنصر
  double get totalPrice {
    if (product == null) return 0;
    return product!.effectivePrice * quantity;
  }

  /// السعر قبل الخصم
  double get originalTotal {
    if (product == null) return 0;
    return product!.price * quantity;
  }

  /// مبلغ التوفير
  double get savings => originalTotal - totalPrice;

  factory CartItem.fromJson(Map<String, dynamic> json) {
    return CartItem(
      id: json['id'] as int?,
      userId: json['user_id'] as int? ?? 0,
      productId: json['product_id'] as int? ?? 0,
      product: json['product'] != null
          ? Product.fromJson(json['product'])
          : null,
      quantity: json['quantity'] as int? ?? 1,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'product_id': productId,
      'product': product?.toJson(),
      'quantity': quantity,
      'created_at': createdAt?.toIso8601String(),
    };
  }

  Map<String, dynamic> toMap() {
    return {
      if (id != null) 'id': id,
      'user_id': userId,
      'product_id': productId,
      'quantity': quantity,
    };
  }

  CartItem copyWith({
    int? id,
    int? userId,
    int? productId,
    Product? product,
    int? quantity,
    DateTime? createdAt,
  }) {
    return CartItem(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      productId: productId ?? this.productId,
      product: product ?? this.product,
      quantity: quantity ?? this.quantity,
      createdAt: createdAt ?? this.createdAt,
    );
  }

  @override
  String toString() {
    return 'CartItem(productId: $productId, quantity: $quantity)';
  }
}
