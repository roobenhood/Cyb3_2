import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../providers/products_provider.dart';
import '../widgets/product_card.dart';
import '../widgets/category_chip.dart';

class ProductsScreen extends StatefulWidget {
  const ProductsScreen({super.key});

  @override
  State<ProductsScreen> createState() => _ProductsScreenState();
}

class _ProductsScreenState extends State<ProductsScreen> {
  final ScrollController _scrollController = ScrollController();
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      final provider = Provider.of<ProductsProvider>(context, listen: false);
      if (!provider.isLoading && provider.hasMore) {
        provider.fetchProducts();
      }
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('المنتجات'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterSheet,
          ),
        ],
      ),
      body: Column(
        children: [
          // شريط البحث
          Padding(
            padding: EdgeInsets.all(16.w(context)),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'ابحث عن منتج...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          Provider.of<ProductsProvider>(context, listen: false)
                              .setSearchQuery('');
                        },
                      )
                    : null,
              ),
              onSubmitted: (value) {
                Provider.of<ProductsProvider>(context, listen: false)
                    .setSearchQuery(value);
              },
            ),
          ),

          // التصنيفات
          Consumer<ProductsProvider>(
            builder: (context, provider, child) {
              return SizedBox(
                height: 50.h(context),
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: EdgeInsets.symmetric(horizontal: 12.w(context)),
                  itemCount: provider.categories.length + 1,
                  itemBuilder: (context, index) {
                    if (index == 0) {
                      return CategoryChip(
                        category: null,
                        isSelected: provider.selectedCategoryId == null,
                        onTap: () => provider.setCategory(null),
                      );
                    }
                    final category = provider.categories[index - 1];
                    return CategoryChip(
                      category: category,
                      isSelected: provider.selectedCategoryId == category.id,
                      onTap: () => provider.setCategory(category.id),
                    );
                  },
                ),
              );
            },
          ),

          SizedBox(height: 8.h(context)),

          // قائمة المنتجات
          Expanded(
            child: Consumer<ProductsProvider>(
              builder: (context, provider, child) {
                if (provider.isLoading && provider.products.isEmpty) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (provider.products.isEmpty) {
                  return Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.inventory_2_outlined,
                          size: 64.sp(context),
                          color: Theme.of(context).disabledColor,
                        ),
                        SizedBox(height: 16.h(context)),
                        Text(
                          'لا توجد منتجات',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                        if (provider.selectedCategoryId != null)
                          TextButton(
                            onPressed: () => provider.clearFilters(),
                            child: const Text('مسح الفلاتر'),
                          ),
                      ],
                    ),
                  );
                }

                return RefreshIndicator(
                  onRefresh: () => provider.fetchProducts(refresh: true),
                  child: GridView.builder(
                    controller: _scrollController,
                    padding: EdgeInsets.all(16.w(context)),
                    gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2,
                      childAspectRatio: 0.7,
                      crossAxisSpacing: 12.w(context),
                      mainAxisSpacing: 12.h(context),
                    ),
                    itemCount:
                        provider.products.length + (provider.hasMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index >= provider.products.length) {
                        return Center(
                          child: Padding(
                            padding: EdgeInsets.all(16.w(context)),
                            child: const CircularProgressIndicator(),
                          ),
                        );
                      }
                      return ProductCard(product: provider.products[index]);
                    },
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  void _showFilterSheet() {
    showModalBottomSheet(
      context: context,
      builder: (context) {
        return Consumer<ProductsProvider>(
          builder: (context, provider, child) {
            return Padding(
              padding: EdgeInsets.all(24.w(context)),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'ترتيب حسب',
                    style: Theme.of(context).textTheme.titleLarge,
                  ),
                  SizedBox(height: 16.h(context)),
                  ListTile(
                    title: const Text('الأحدث'),
                    leading: const Icon(Icons.access_time),
                    onTap: () {
                      provider.setSortBy('newest');
                      Navigator.pop(context);
                    },
                  ),
                  ListTile(
                    title: const Text('السعر: من الأقل للأعلى'),
                    leading: const Icon(Icons.arrow_upward),
                    onTap: () {
                      provider.setSortBy('price_asc');
                      Navigator.pop(context);
                    },
                  ),
                  ListTile(
                    title: const Text('السعر: من الأعلى للأقل'),
                    leading: const Icon(Icons.arrow_downward),
                    onTap: () {
                      provider.setSortBy('price_desc');
                      Navigator.pop(context);
                    },
                  ),
                  ListTile(
                    title: const Text('الأكثر تقييماً'),
                    leading: const Icon(Icons.star),
                    onTap: () {
                      provider.setSortBy('rating');
                      Navigator.pop(context);
                    },
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
