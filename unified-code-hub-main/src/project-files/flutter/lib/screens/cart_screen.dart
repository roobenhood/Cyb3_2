import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/cart_provider.dart';
import '../providers/auth_provider.dart';
import '../providers/courses_provider.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});
  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    return Scaffold(
      appBar: AppBar(title: const Text('السلة')),
      body: Consumer<CartProvider>(builder: (context, cart, child) {
        if (cart.items.isEmpty) return const Center(child: Text('السلة فارغة'));
        return ListView.builder(
          itemCount: cart.items.length,
          itemBuilder: (context, index) {
            final course = cart.items[index];
            return ListTile(
              title: Text(course.title),
              subtitle: Text('${course.effectivePrice} \$'),
              trailing: IconButton(icon: const Icon(Icons.delete), onPressed: () => cart.removeFromCart(user!.id!, course.id!)),
            );
          },
        );
      }),
      bottomNavigationBar: Consumer<CartProvider>(builder: (context, cart, child) => Padding(
        padding: const EdgeInsets.all(16),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            const Text('المجموع:', style: TextStyle(fontSize: 18)),
            Text('${cart.total} \$', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ]),
          const SizedBox(height: 12),
          SizedBox(width: double.infinity, child: ElevatedButton(
            onPressed: cart.items.isEmpty ? null : () async {
              final coursesProvider = context.read<CoursesProvider>();
              for (var course in cart.items) {
                await coursesProvider.enrollCourse(user!.id!, course.id!);
              }
              await cart.clearCart(user!.id!);
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم التسجيل بنجاح!')));
              Navigator.pop(context);
            },
            child: const Text('إتمام الشراء'),
          )),
        ]),
      )),
    );
  }
}
