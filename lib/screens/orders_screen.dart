import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../models/order.dart';
import '../services/api_service.dart';
import '../providers/auth_provider.dart';
import '../utils/formatters.dart';

class OrdersScreen extends StatefulWidget {
  const OrdersScreen({super.key});

  @override
  State<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends State<OrdersScreen> {
  List<Order> _orders = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders() async {
    final auth = Provider.of<AuthProvider>(context, listen: false);
    if (!auth.isAuthenticated) {
      setState(() {
        _isLoading = false;
        _error = 'يجب تسجيل الدخول لعرض الطلبات';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final apiService = ApiService();
      final response = await apiService.get('orders.php');

      if (response['success']) {
        final data = response['data'];
        _orders = (data['orders'] as List)
            .map((o) => Order.fromJson(o))
            .toList();
      } else {
        _error = response['message'];
      }
    } catch (e) {
      _error = 'حدث خطأ أثناء تحميل الطلبات';
    }

    setState(() => _isLoading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('طلباتي'),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return Center(
        child: SizedBox(
          width: 40.w(context),
          height: 40.h(context),
          child: const CircularProgressIndicator(),
        ),
      );
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.error_outline,
              size: 64.sp(context),
              color: Theme.of(context).disabledColor,
            ),
            SizedBox(height: 16.h(context)),
            Text(_error!),
            SizedBox(height: 16.h(context)),
            ElevatedButton(
              onPressed: _loadOrders,
              child: const Text('إعادة المحاولة'),
            ),
          ],
        ),
      );
    }

    if (_orders.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.receipt_long_outlined,
              size: 100.sp(context),
              color: Theme.of(context).disabledColor,
            ),
            SizedBox(height: 16.h(context)),
            Text(
              'لا توجد طلبات',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            SizedBox(height: 8.h(context)),
            Text(
              'ابدأ التسوق الآن',
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

    return RefreshIndicator(
      onRefresh: _loadOrders,
      child: ListView.builder(
        padding: EdgeInsets.all(16.w(context)),
        itemCount: _orders.length,
        itemBuilder: (context, index) {
          return _buildOrderCard(_orders[index]);
        },
      ),
    );
  }

  Widget _buildOrderCard(Order order) {
    return Card(
      margin: EdgeInsets.only(bottom: 16.h(context)),
      child: InkWell(
        onTap: () => _showOrderDetails(order),
        borderRadius: BorderRadius.circular(12.w(context)),
        child: Padding(
          padding: EdgeInsets.all(16.w(context)),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'طلب #${order.id}',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                  _buildStatusChip(order.status),
                ],
              ),
              SizedBox(height: 8.h(context)),
              Text(
                Formatters.formatDate(order.createdAt),
                style: Theme.of(context).textTheme.bodySmall,
              ),
              Divider(height: 24.h(context)),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('${order.items.length} منتجات'),
                  Text(
                    Formatters.formatPrice(order.total),
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusChip(String status) {
    Color color;
    IconData icon;

    switch (status) {
      case 'pending':
        color = Colors.orange;
        icon = Icons.schedule;
        break;
      case 'processing':
        color = Colors.blue;
        icon = Icons.autorenew;
        break;
      case 'shipped':
        color = Colors.purple;
        icon = Icons.local_shipping;
        break;
      case 'delivered':
        color = Colors.green;
        icon = Icons.check_circle;
        break;
      case 'cancelled':
        color = Colors.red;
        icon = Icons.cancel;
        break;
      default:
        color = Colors.grey;
        icon = Icons.info;
    }

    return Container(
      padding: EdgeInsets.symmetric(
          horizontal: 8.w(context), vertical: 4.h(context)),
      decoration: BoxDecoration(
        color: color.withAlpha(25),
        borderRadius: BorderRadius.circular(16.w(context)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16.sp(context), color: color),
          SizedBox(width: 4.w(context)),
          Text(
            Order(
              id: 0,
              userId: 0,
              subtotal: 0,
              total: 0,
              status: status,
              createdAt: DateTime.now(),
            ).statusText,
            style: TextStyle(
              color: color,
              fontSize: 12.sp(context),
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  void _showOrderDetails(Order order) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        maxChildSize: 0.9,
        minChildSize: 0.5,
        expand: false,
        builder: (context, scrollController) {
          return Padding(
            padding: EdgeInsets.all(16.w(context)),
            child: ListView(
              controller: scrollController,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'طلب #${order.id}',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    _buildStatusChip(order.status),
                  ],
                ),
                SizedBox(height: 8.h(context)),
                Text(
                  Formatters.formatDate(order.createdAt),
                  style: Theme.of(context).textTheme.bodySmall,
                ),
                Divider(height: 32.h(context)),

                // عنوان التوصيل
                if (order.shippingAddress != null) ...[
                  _buildInfoRow(
                    Icons.location_on_outlined,
                    'عنوان التوصيل',
                    order.shippingAddress!,
                  ),
                  SizedBox(height: 12.h(context)),
                ],

                // طريقة الدفع
                if (order.paymentMethod != null) ...[
                  _buildInfoRow(
                    Icons.payment,
                    'طريقة الدفع',
                    order.paymentMethod == 'cash'
                        ? 'الدفع عند الاستلام'
                        : 'بطاقة ائتمان',
                  ),
                  SizedBox(height: 12.h(context)),
                ],

                // ملاحظات
                if (order.notes != null && order.notes!.isNotEmpty) ...[
                  _buildInfoRow(
                    Icons.note_outlined,
                    'ملاحظات',
                    order.notes!,
                  ),
                  SizedBox(height: 12.h(context)),
                ],

                Divider(height: 32.h(context)),

                // المنتجات
                Text(
                  'المنتجات',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                SizedBox(height: 8.h(context)),
                ...order.items.map((item) => ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(item.product?.name ?? 'منتج'),
                      subtitle: Text(
                          '${item.quantity} × ${Formatters.formatPrice(item.price)}'),
                      trailing: Text(
                        Formatters.formatPrice(item.total),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                    )),

                Divider(height: 32.h(context)),

                // الملخص
                _buildSummaryRow('المجموع الفرعي', order.subtotal),
                _buildSummaryRow('الشحن', order.shipping),
                _buildSummaryRow('الضريبة', order.tax),
                const Divider(),
                _buildSummaryRow('الإجمالي', order.total, isTotal: true),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 20.sp(context), color: Theme.of(context).disabledColor),
        SizedBox(width: 8.w(context)),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: Theme.of(context).textTheme.bodySmall,
              ),
              Text(value),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryRow(String label, double value, {bool isTotal = false}) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4.h(context)),
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
