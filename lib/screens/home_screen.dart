import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../providers/products_provider.dart';
import '../providers/cart_provider.dart';
import '../providers/auth_provider.dart';
import '../widgets/product_card.dart';
import '../widgets/category_chip.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    // تحميل البيانات بعد بناء الواجهة
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadData();
    });
  }

  Future<void> _loadData() async {
    final productsProvider = Provider.of<ProductsProvider>(context, listen: false);
    // جلب كل البيانات اللازمة في وقت واحد
    await Future.wait([
      productsProvider.fetchCategories(),
      productsProvider.fetchFeaturedProducts(),
      productsProvider.fetchProducts(refresh: true),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('متجري'),
        actions: [
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: () {
              // TODO: فتح صفحة البحث
            },
          ),
          Consumer<CartProvider>(
            builder: (context, cart, child) {
              return Stack(
                children: [
                  IconButton(
                    icon: const Icon(Icons.shopping_cart_outlined),
                    onPressed: () {
                      Navigator.of(context).pushNamed('/cart');
                    },
                  ),
                  if (cart.itemCount > 0)
                    Positioned(
                      right: 8.w(context),
                      top: 8.h(context),
                      child: Container(
                        padding: EdgeInsets.all(4.w(context)),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.error,
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          '${cart.itemCount}',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 10.sp(context),
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ),
                ],
              );
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadData,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ===================================
              // 1. بانر ترحيبي (Welcome Banner)
              // ===================================
              Container(
                margin: EdgeInsets.all(16.w(context)),
                padding: EdgeInsets.all(20.w(context)),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      Theme.of(context).colorScheme.primary,
                      Theme.of(context).colorScheme.secondary,
                    ],
                  ),
                  borderRadius: BorderRadius.circular(16.w(context)),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Consumer<AuthProvider>(
                            builder: (context, auth, child) {
                              return Text(
                                auth.isAuthenticated
                                    ? 'مرحباً ${auth.user?.name}'
                                    : 'مرحباً بك',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 20.sp(context),
                                  fontWeight: FontWeight.bold,
                                ),
                              );
                            },
                          ),
                          SizedBox(height: 4.h(context)),
                          Text(
                            'اكتشف أحدث المنتجات',
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.9),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Icon(
                      Icons.local_offer,
                      color: Colors.white,
                      size: 48.sp(context),
                    ),
                  ],
                ),
              ),

              // ===================================
              // 2. التصنيفات (Categories)
              // ===================================
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 16.w(context)),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'التصنيفات',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.of(context).pushNamed('/products');
                      },
                      child: const Text('عرض الكل'),
                    ),
                  ],
                ),
              ),
              Consumer<ProductsProvider>(
                builder: (context, provider, child) {
                  return SizedBox(
                    height: 50.h(context),
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      padding: EdgeInsets.symmetric(horizontal: 12.w(context)),
                      itemCount: provider.categories.length,
                      itemBuilder: (context, index) {
                        final category = provider.categories[index];
                        return CategoryChip(
                          category: category,
                          isSelected: provider.selectedCategoryId == category.id,
                          onTap: () {
                            provider.setCategory(category.id);
                            Navigator.of(context).pushNamed('/products');
                          },
                        );
                      },
                    ),
                  );
                },
              ),

              SizedBox(height: 16.h(context)),

              // ===================================
              // 3. المنتجات المميزة (Featured Products)
              // ===================================
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 16.w(context)),
                child: Text(
                  'منتجات مميزة',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              SizedBox(height: 8.h(context)),
              Consumer<ProductsProvider>(
                builder: (context, provider, child) {
                  if (provider.isLoading && provider.featuredProducts.isEmpty) {
                    return Center(
                      child: Padding(
                        padding: EdgeInsets.all(32.w(context)),
                        child: const CircularProgressIndicator(),
                      ),
                    );
                  }
                  return SizedBox(
                    height: 280.h(context), // تأكد أن هذا الارتفاع كافٍ للكارت
                    child: ListView.builder(
                      scrollDirection: Axis.horizontal,
                      padding: EdgeInsets.symmetric(horizontal: 12.w(context)),
                      itemCount: provider.featuredProducts.length,
                      itemBuilder: (context, index) {
                        return SizedBox(
                          width: 180.w(context),
                          child: ProductCard(
                            product: provider.featuredProducts[index],
                          ),
                        );
                      },
                    ),
                  );
                },
              ),

              SizedBox(height: 16.h(context)),

              // ===================================
              // 4. أحدث المنتجات (Latest Products Grid)
              // ===================================
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 16.w(context)),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'أحدث المنتجات',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.of(context).pushNamed('/products');
                      },
                      child: const Text('عرض الكل'),
                    ),
                  ],
                ),
              ),
              Consumer<ProductsProvider>(
                builder: (context, provider, child) {
                  return GridView.builder(
                    shrinkWrap: true, // ضروري لأن GridView داخل SingleChildScrollView
                    physics: const NeverScrollableScrollPhysics(), // تعطيل السكرول الداخلي
                    padding: EdgeInsets.all(16.w(context)),
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      // ---------------------------------------------------------
                      // [الحل الجذري]: تغيير النسبة من 0.7 إلى 0.6
                      // كلما قل الرقم، زاد طول الكارت رأسياً
                      // هذا يوفر مساحة كافية للصور + النصوص + الأزرار بدون Overflow
                      // ---------------------------------------------------------
                      childAspectRatio: 0.6,
                      crossAxisSpacing: 12.w(context),
                      mainAxisSpacing: 12.h(context),
                    ),
                    itemCount: provider.products.length > 6 ? 6 : provider.products.length,
                    itemBuilder: (context, index) {
                      return ProductCard(product: provider.products[index]);
                    },
                  );
                },
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _currentIndex,
        onDestinationSelected: (index) {
          setState(() => _currentIndex = index);
          switch (index) {
            case 0:
            // الرئيسية - نحن هنا
              break;
            case 1:
              Navigator.of(context).pushNamed('/products');
              break;
            case 2:
              Navigator.of(context).pushNamed('/favorites');
              break;
            case 3:
              Navigator.of(context).pushNamed('/profile');
              break;
          }
        },
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'الرئيسية',
          ),
          NavigationDestination(
            icon: Icon(Icons.grid_view_outlined),
            selectedIcon: Icon(Icons.grid_view),
            label: 'المنتجات',
          ),
          NavigationDestination(
            icon: Icon(Icons.favorite_outline),
            selectedIcon: Icon(Icons.favorite),
            label: 'المفضلة',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'حسابي',
          ),
        ],
      ),
    );
  }
}