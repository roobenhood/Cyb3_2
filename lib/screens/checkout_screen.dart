import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../providers/cart_provider.dart';
import '../providers/auth_provider.dart';
import '../services/api_service.dart';
import '../utils/formatters.dart';
import '../utils/validators.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _formKey = GlobalKey<FormState>();
  final _addressController = TextEditingController();
  final _phoneController = TextEditingController();
  final _notesController = TextEditingController();

  String _paymentMethod = 'cash';
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  void _loadUserData() {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    if (auth.user != null) {
      _addressController.text = auth.user!.address ?? '';
      _phoneController.text = auth.user!.phone ?? '';
    }
  }

  @override
  void dispose() {
    _addressController.dispose();
    _phoneController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submitOrder() async {
    if (!_formKey.currentState!.validate()) return;

    final auth = Provider.of<AuthProvider>(context, listen: false);
    if (!auth.isAuthenticated) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يجب تسجيل الدخول أولاً')),
      );
      Navigator.of(context).pushNamed('/login');
      return;
    }

    setState(() => _isLoading = true);

    try {
      final cart = Provider.of<CartProvider>(context, listen: false);
      final apiService = ApiService();

      final response = await apiService.post('orders.php', {
        'items': cart.items
            .map((item) => {
                  'product_id': item.productId,
                  'quantity': item.quantity,
                  'price': item.price,
                })
            .toList(),
        'shipping_address': _addressController.text,
        'phone': _phoneController.text,
        'payment_method': _paymentMethod,
        'notes': _notesController.text,
        'subtotal': cart.subtotal,
        'shipping': cart.shipping,
        'tax': cart.tax,
        'total': cart.total,
      });

      if (response['success'] && mounted) {
        await cart.clearCart();
        _showSuccessDialog();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(response['message'] ?? 'فشل إرسال الطلب')),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('حدث خطأ أثناء إرسال الطلب')),
      );
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        icon: Icon(
          Icons.check_circle,
          color: Colors.green,
          size: 64.sp(context),
        ),
        title: const Text('تم إرسال طلبك بنجاح'),
        content: const Text('سنتواصل معك قريباً لتأكيد الطلب وتفاصيل التوصيل'),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.of(context).pushNamedAndRemoveUntil(
                '/orders',
                (route) => route.settings.name == '/home',
              );
            },
            child: const Text('تتبع الطلب'),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pushNamedAndRemoveUntil(
                '/home',
                (route) => false,
              );
            },
            child: const Text('العودة للرئيسية'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('إتمام الشراء'),
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, child) {
          return SingleChildScrollView(
            padding: EdgeInsets.all(16.w(context)),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // عنوان التوصيل
                  Text(
                    'عنوان التوصيل',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  SizedBox(height: 8.h(context)),
                  TextFormField(
                    controller: _addressController,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      hintText: 'أدخل عنوان التوصيل بالتفصيل',
                      prefixIcon: Icon(Icons.location_on_outlined),
                    ),
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'العنوان مطلوب';
                      }
                      return null;
                    },
                  ),
                  SizedBox(height: 16.h(context)),

                  // رقم الهاتف
                  Text(
                    'رقم الهاتف',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  SizedBox(height: 8.h(context)),
                  TextFormField(
                    controller: _phoneController,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(
                      hintText: 'أدخل رقم الهاتف للتواصل',
                      prefixIcon: Icon(Icons.phone_outlined),
                    ),
                    validator: Validators.validatePhone,
                  ),
                  SizedBox(height: 16.h(context)),

                  // طريقة الدفع
                  Text(
                    'طريقة الدفع',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  SizedBox(height: 8.h(context)),
                  RadioListTile<String>(
                    value: 'cash',
                    groupValue: _paymentMethod,
                    onChanged: (value) {
                      setState(() => _paymentMethod = value!);
                    },
                    title: const Text('الدفع عند الاستلام'),
                    secondary: const Icon(Icons.money),
                  ),
                  RadioListTile<String>(
                    value: 'card',
                    groupValue: _paymentMethod,
                    onChanged: (value) {
                      setState(() => _paymentMethod = value!);
                    },
                    title: const Text('بطاقة ائتمان'),
                    secondary: const Icon(Icons.credit_card),
                  ),
                  SizedBox(height: 16.h(context)),

                  // ملاحظات
                  Text(
                    'ملاحظات إضافية',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  SizedBox(height: 8.h(context)),
                  TextFormField(
                    controller: _notesController,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      hintText: 'أي ملاحظات خاصة بالطلب (اختياري)',
                      prefixIcon: Icon(Icons.note_outlined),
                    ),
                  ),
                  SizedBox(height: 24.h(context)),

                  // ملخص الطلب
                  Card(
                    child: Padding(
                      padding: EdgeInsets.all(16.w(context)),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'ملخص الطلب',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(
                                  fontWeight: FontWeight.bold,
                                ),
                          ),
                          const Divider(),
                          ...cart.items
                              .map((item) => Padding(
                                    padding: EdgeInsets.symmetric(vertical: 4.h(context)),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Expanded(
                                          child: Text(
                                            '${item.product?.name ?? 'منتج'} × ${item.quantity}',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                        Text(Formatters.formatPrice(item.total)),
                                      ],
                                    ),
                                  )),
                          const Divider(),
                          _buildRow('المجموع الفرعي', cart.subtotal),
                          _buildRow('الشحن', cart.shipping),
                          _buildRow('الضريبة', cart.tax),
                          const Divider(),
                          _buildRow('الإجمالي', cart.total, isTotal: true),
                        ],
                      ),
                    ),
                  ),
                  SizedBox(height: 24.h(context)),

                  // زر تأكيد الطلب
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _isLoading ? null : _submitOrder,
                      child: _isLoading
                          ? SizedBox(
                              height: 20.h(context),
                              width: 20.w(context),
                              child:
                                  const CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Text(
                              'تأكيد الطلب (${Formatters.formatPrice(cart.total)})'),
                    ),
                  ),
                  SizedBox(height: 32.h(context)),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildRow(String label, double value, {bool isTotal = false}) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 2.h(context)),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: isTotal ? const TextStyle(fontWeight: FontWeight.bold) : null,
          ),
          Text(
            Formatters.formatPrice(value),
            style: isTotal
                ? TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).colorScheme.primary,
                    fontSize: 18.sp(context),
                  )
                : null,
          ),
        ],
      ),
    );
  }
}
