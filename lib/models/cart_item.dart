import 'product.dart';

class CartItem {
  final int id;
  final int productId;
  final Product? product;
  final int quantity;
  final double price;
  final DateTime createdAt;

  CartItem({
    required this.id,
    required this.productId,
    this.product,
    required this.quantity,
    required this.price,
    required this.createdAt,
  });

  factory CartItem.fromJson(Map<String, dynamic> json) {
    return CartItem(
      id: json['id'] ?? 0,
      productId: json['product_id'] ?? 0,
      product: json['product'] != null 
          ? Product.fromJson(json['product']) 
          : null,
      quantity: json['quantity'] ?? 1,
      price: (json['price'] ?? 0).toDouble(),
      createdAt: json['created_at'] != null 
          ? DateTime.parse(json['created_at']) 
          : DateTime.now(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'product_id': productId,
      'product': product?.toJson(),
      'quantity': quantity,
      'price': price,
      'created_at': createdAt.toIso8601String(),
    };
  }

  double get total => price * quantity;

  CartItem copyWith({
    int? id,
    int? productId,
    Product? product,
    int? quantity,
    double? price,
    DateTime? createdAt,
  }) {
    return CartItem(
      id: id ?? this.id,
      productId: productId ?? this.productId,
      product: product ?? this.product,
      quantity: quantity ?? this.quantity,
      price: price ?? this.price,
      createdAt: createdAt ?? this.createdAt,
    );
  }
}
