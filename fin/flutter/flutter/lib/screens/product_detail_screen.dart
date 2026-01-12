import 'package:flutter/material.dart';
class ProductDetailScreen extends StatelessWidget {
  final int productId;
  const ProductDetailScreen({super.key, required this.productId});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('تفاصيل المنتج')), body: Center(child: Text('Product ID: $productId')));
}
