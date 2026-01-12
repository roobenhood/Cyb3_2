import 'cart_item.dart';

/// حالة الطلب
enum OrderStatus {
  pending,      // قيد الانتظار
  confirmed,    // مؤكد
  processing,   // قيد المعالجة
  shipped,      // تم الشحن
  delivered,    // تم التوصيل
  cancelled,    // ملغي
  refunded,     // مسترجع
}

/// نموذج الطلب
class Order {
  final int? id;
  final int userId;
  final String orderNumber;
  final List<CartItem> items;
  final double subtotal;
  final double shippingCost;
  final double discount;
  final double total;
  final OrderStatus status;
  final String? shippingAddress;
  final String? paymentMethod;
  final String? notes;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Order({
    this.id,
    required this.userId,
    required this.orderNumber,
    this.items = const [],
    required this.subtotal,
    this.shippingCost = 0,
    this.discount = 0,
    required this.total,
    this.status = OrderStatus.pending,
    this.shippingAddress,
    this.paymentMethod,
    this.notes,
    this.createdAt,
    this.updatedAt,
  });

  /// عدد المنتجات في الطلب
  int get itemCount => items.fold(0, (sum, item) => sum + item.quantity);

  /// هل الطلب نشط؟
  bool get isActive => status != OrderStatus.cancelled && 
                       status != OrderStatus.refunded && 
                       status != OrderStatus.delivered;

  /// هل يمكن إلغاء الطلب؟
  bool get canCancel => status == OrderStatus.pending || status == OrderStatus.confirmed;

  /// الحصول على اسم الحالة بالعربية
  String get statusName {
    switch (status) {
      case OrderStatus.pending:
        return 'قيد الانتظار';
      case OrderStatus.confirmed:
        return 'مؤكد';
      case OrderStatus.processing:
        return 'قيد المعالجة';
      case OrderStatus.shipped:
        return 'تم الشحن';
      case OrderStatus.delivered:
        return 'تم التوصيل';
      case OrderStatus.cancelled:
        return 'ملغي';
      case OrderStatus.refunded:
        return 'مسترجع';
    }
  }

  factory Order.fromJson(Map<String, dynamic> json) {
    return Order(
      id: json['id'] as int?,
      userId: json['user_id'] as int? ?? 0,
      orderNumber: json['order_number'] as String? ?? '',
      items: json['items'] != null
          ? (json['items'] as List).map((e) => CartItem.fromJson(e)).toList()
          : [],
      subtotal: (json['subtotal'] as num?)?.toDouble() ?? 0.0,
      shippingCost: (json['shipping_cost'] as num?)?.toDouble() ?? 0.0,
      discount: (json['discount'] as num?)?.toDouble() ?? 0.0,
      total: (json['total'] as num?)?.toDouble() ?? 0.0,
      status: _parseStatus(json['status'] as String?),
      shippingAddress: json['shipping_address'] as String?,
      paymentMethod: json['payment_method'] as String?,
      notes: json['notes'] as String?,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString())
          : null,
    );
  }

  static OrderStatus _parseStatus(String? status) {
    switch (status) {
      case 'confirmed':
        return OrderStatus.confirmed;
      case 'processing':
        return OrderStatus.processing;
      case 'shipped':
        return OrderStatus.shipped;
      case 'delivered':
        return OrderStatus.delivered;
      case 'cancelled':
        return OrderStatus.cancelled;
      case 'refunded':
        return OrderStatus.refunded;
      default:
        return OrderStatus.pending;
    }
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'order_number': orderNumber,
      'items': items.map((e) => e.toJson()).toList(),
      'subtotal': subtotal,
      'shipping_cost': shippingCost,
      'discount': discount,
      'total': total,
      'status': status.name,
      'shipping_address': shippingAddress,
      'payment_method': paymentMethod,
      'notes': notes,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  Order copyWith({
    int? id,
    int? userId,
    String? orderNumber,
    List<CartItem>? items,
    double? subtotal,
    double? shippingCost,
    double? discount,
    double? total,
    OrderStatus? status,
    String? shippingAddress,
    String? paymentMethod,
    String? notes,
    DateTime? createdAt,
    DateTime? updatedAt,
  }) {
    return Order(
      id: id ?? this.id,
      userId: userId ?? this.userId,
      orderNumber: orderNumber ?? this.orderNumber,
      items: items ?? this.items,
      subtotal: subtotal ?? this.subtotal,
      shippingCost: shippingCost ?? this.shippingCost,
      discount: discount ?? this.discount,
      total: total ?? this.total,
      status: status ?? this.status,
      shippingAddress: shippingAddress ?? this.shippingAddress,
      paymentMethod: paymentMethod ?? this.paymentMethod,
      notes: notes ?? this.notes,
      createdAt: createdAt ?? this.createdAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  String toString() {
    return 'Order(id: $id, orderNumber: $orderNumber, total: $total, status: $status)';
  }
}
