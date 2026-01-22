import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../providers/cart_provider.dart';
import '../utils/formatters.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('سلة التسوق'),
        actions: [
          Consumer<CartProvider>(
            builder: (context, cart, child) {
              if (cart.items.isEmpty) return const SizedBox.shrink();
              return TextButton(
                onPressed: () => _showClearCartDialog(context, cart),
                child: const Text('مسح الكل'),
              );
            },
          ),
        ],
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, child) {
          if (cart.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (cart.items.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.shopping_cart_outlined,
                    size: 100.sp(context),
                    color: Theme.of(context).disabledColor,
                  ),
                  SizedBox(height: 16.h(context)),
                  Text(
                    'السلة فارغة',
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  SizedBox(height: 8.h(context)),
                  Text(
                    'أضف منتجات للسلة لتبدأ التسوق',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Theme.of(context).disabledColor,
                        ),
                  ),
                  SizedBox(height: 24.h(context)),
                  ElevatedButton(
                    onPressed: () {
                      Navigator.of(context).pushReplacementNamed('/products');
                    },
                    child: const Text('تصفح المنتجات'),
                  ),
                ],
              ),
            );
          }

          return Column(
            children: [
              // قائمة المنتجات
              Expanded(
                child: ListView.builder(
                  padding: EdgeInsets.all(16.w(context)),
                  itemCount: cart.items.length,
                  itemBuilder: (context, index) {
                    final item = cart.items[index];
                    return Card(
                      margin: EdgeInsets.only(bottom: 12.h(context)),
                      child: Padding(
                        padding: EdgeInsets.all(12.w(context)),
                        child: Row(
                          children: [
                            // صورة المنتج
                            ClipRRect(
                              borderRadius: BorderRadius.circular(8.w(context)),
                              child: Container(
                                width: 80.w(context),
                                height: 80.h(context),
                                color: Colors.grey[200],
                                child: item.product?.image != null
                                    ? Image.network(
                                        item.product!.image!,
                                        fit: BoxFit.cover,
                                        errorBuilder: (_, __, ___) =>
                                            const Icon(Icons.image),
                                      )
                                    : const Icon(Icons.image),
                              ),
                            ),
                            SizedBox(width: 12.w(context)),
                            // تفاصيل المنتج
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item.product?.name ?? 'منتج #${item.productId}',
                                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.bold), // [الحل النهائي]: تصغير الخط ليتوافق مع بطاقة المنتج
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  SizedBox(height: 4.h(context)),
                                  Text(
                                    Formatters.formatPrice(item.price),
                                    style: TextStyle(
                                      color: Theme.of(context)
                                          .colorScheme
                                          .primary,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  SizedBox(height: 8.h(context)),
                                  // تحكم بالكمية
                                  Row(
                                    children: [
                                      IconButton(
                                        onPressed: () {
                                          cart.updateQuantity(
                                            item.productId,
                                            item.quantity - 1,
                                          );
                                        },
                                        icon: const Icon(
                                            Icons.remove_circle_outline),
                                        iconSize: 24.sp(context),
                                        padding: EdgeInsets.zero,
                                        constraints: const BoxConstraints(),
                                      ),
                                      SizedBox(
                                        width: 40.w(context),
                                        child: Text(
                                          '${item.quantity}',
                                          textAlign: TextAlign.center,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      IconButton(
                                        onPressed: () {
                                          cart.updateQuantity(
                                            item.productId,
                                            item.quantity + 1,
                                          );
                                        },
                                        icon: const Icon(
                                            Icons.add_circle_outline),
                                        iconSize: 24.sp(context),
                                        padding: EdgeInsets.zero,
                                        constraints: const BoxConstraints(),
                                      ),
                                      const Spacer(),
                                      IconButton(
                                        onPressed: () {
                                          cart.removeFromCart(item.productId);
                                        },
                                        icon: const Icon(Icons.delete_outline),
                                        color: Colors.red,
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),

              // ملخص الطلب
              Container(
                padding: EdgeInsets.all(16.w(context)),
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  borderRadius: BorderRadius.vertical(
                    top: Radius.circular(24.w(context)),
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 10.w(context),
                      offset: Offset(0, -5.h(context)),
                    ),
                  ],
                ),
                child: SafeArea(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      _buildSummaryRow(
                        context,
                        'المجموع الفرعي',
                        Formatters.formatPrice(cart.subtotal),
                      ),
                      _buildSummaryRow(
                        context,
                        'الشحن',
                        cart.shipping == 0
                            ? 'مجاني'
                            : Formatters.formatPrice(cart.shipping),
                      ),
                      _buildSummaryRow(
                        context,
                        'الضريبة (15%)',
                        Formatters.formatPrice(cart.tax),
                      ),
                      const Divider(),
                      _buildSummaryRow(
                        context,
                        'الإجمالي',
                        Formatters.formatPrice(cart.total),
                        isTotal: true,
                      ),
                      SizedBox(height: 16.h(context)),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: () {
                            Navigator.of(context).pushNamed('/checkout');
                          },
                          child: const Text('إتمام الشراء'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildSummaryRow(
    BuildContext context,
    String label,
    String value, {
    bool isTotal = false,
  }) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4.h(context)),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: isTotal
                ? Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    )
                : null,
          ),
          Text(
            value,
            style: isTotal
                ? Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.primary,
                    )
                : null,
          ),
        ],
      ),
    );
  }

  void _showClearCartDialog(BuildContext context, CartProvider cart) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('مسح السلة'),
        content: const Text('هل أنت متأكد من حذف جميع المنتجات من السلة؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('إلغاء'),
          ),
          ElevatedButton(
            onPressed: () {
              cart.clearCart();
              Navigator.pop(context);
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
            ),
            child: const Text('مسح'),
          ),
        ],
      ),
    );
  }
}