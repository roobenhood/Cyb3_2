import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../conestant/responsiveSize.dart';
import '../config/app_config.dart';
import '../models/product.dart';
import '../providers/favorites_provider.dart';
import '../providers/cart_provider.dart';
import '../utils/formatters.dart';

class ProductCard extends StatelessWidget {
  final Product product;
  final bool showFavoriteButton;

  const ProductCard({
    super.key,
    required this.product,
    this.showFavoriteButton = true,
  });

  String _getValidImageUrl(String? imageUrl) {
    if (imageUrl == null || imageUrl.isEmpty) {
      return '';
    }
    if (imageUrl.startsWith('http')) {
      return imageUrl;
    }
    return AppConfig.imagesBaseUrl + imageUrl;
  }

  @override
  Widget build(BuildContext context) {
    final textTheme = Theme.of(context).textTheme;
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      clipBehavior: Clip.antiAlias,
      elevation: 2,
      shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12.w(context))),
      child: InkWell(
        onTap: () {
          Navigator.of(context)
              .pushNamed('/product-detail', arguments: product.id);
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // --- Product Image Section ---
            Stack(
              alignment: Alignment.topLeft,
              children: [
                AspectRatio(
                  aspectRatio: 1.1,
                  child: CachedNetworkImage(
                    imageUrl: _getValidImageUrl(product.image),
                    fit: BoxFit.cover,
                    placeholder: (context, url) =>
                        Container(color: Colors.grey[200]),
                    errorWidget: (context, url, error) => Container(
                      color: Colors.grey[200],
                      child: Icon(Icons.image_not_supported,
                          color: Colors.grey, size: 48.sp(context)),
                    ),
                  ),
                ),
                if (product.hasDiscount)
                  Container(
                    margin: EdgeInsets.all(8.w(context)),
                    padding: EdgeInsets.symmetric(
                        horizontal: 8.w(context), vertical: 4.h(context)),
                    decoration: BoxDecoration(
                      color: colorScheme.error,
                      borderRadius: BorderRadius.circular(4.w(context)),
                    ),
                    child: Text(
                      '-${product.discountPercentage.toStringAsFixed(0)}%',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 12.sp(context),
                          fontWeight: FontWeight.bold),
                    ),
                  ),
                if (showFavoriteButton)
                  Positioned(
                    top: 4.h(context),
                    right: 4.w(context),
                    child: Consumer<FavoritesProvider>(
                      builder: (context, favorites, child) {
                        return IconButton(
                          icon: Icon(
                            favorites.isFavorite(product.id)
                                ? Icons.favorite
                                : Icons.favorite_border,
                            color: favorites.isFavorite(product.id)
                                ? Colors.red.shade400
                                : Colors.black54,
                          ),
                          style: IconButton.styleFrom(
                              backgroundColor: Colors.white.withAlpha(179)),
                          onPressed: () => favorites.toggleFavorite(product),
                        );
                      },
                    ),
                  ),
                if (!product.inStock)
                  Positioned.fill(
                    child: Container(
                      color: Colors.black.withAlpha(153),
                      alignment: Alignment.center,
                      child: Text(
                        'نفذ من المخزون',
                        style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 16.sp(context)),
                      ),
                    ),
                  ),
              ],
            ),

            // --- Product Info Section ---
            // [Fix]: This Expanded ensures the column takes remaining space
            Expanded(
              child: Padding(
                padding: EdgeInsets.symmetric(
                    horizontal: 8.w(context), vertical: 4.h(context)),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.start, // Changed from spaceBetween to avoid overflow
                  children: [
                    // [Fix]: Flexible allows text to shrink if needed
                    Flexible(
                      child: Text(
                        product.name,
                        maxLines: 1, // Changed to 1 to be safer, or keep 2 if space allows
                        overflow: TextOverflow.ellipsis,
                        style: textTheme.bodyLarge
                            ?.copyWith(fontWeight: FontWeight.w500),
                      ),
                    ),

                    if (product.reviewCount > 0) ...[
                      SizedBox(height: 4.h(context)),
                      Row(
                        children: [
                          Icon(Icons.star,
                              color: Colors.amber.shade600,
                              size: 16.sp(context)),
                          SizedBox(width: 4.w(context)),
                          Text(
                            '${product.rating.toStringAsFixed(1)} (${product.reviewCount})',
                            style: textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ],

                    // [Fix]: Spacer pushes the price section to the bottom safely
                    const Spacer(),

                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        // [Fix]: Wrapped in Flexible/Expanded to avoid hitting the cart button
                        Flexible(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (product.hasDiscount)
                                FittedBox( // [Fix]: Scales down text if too big
                                  fit: BoxFit.scaleDown,
                                  child: Text(
                                    Formatters.formatPrice(product.price),
                                    style: textTheme.bodySmall?.copyWith(
                                      decoration: TextDecoration.lineThrough,
                                      color: Colors.grey.shade600,
                                    ),
                                  ),
                                ),
                              FittedBox( // [Fix]: Scales down text if too big
                                fit: BoxFit.scaleDown,
                                child: Text(
                                  Formatters.formatPrice(product.finalPrice),
                                  style: textTheme.titleMedium?.copyWith(
                                    color: colorScheme.primary,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        SizedBox(
                          height: 32.h(context),
                          width: 32.w(context),
                          child: _buildCartButton(context),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCartButton(BuildContext context) {
    return Consumer<CartProvider>(
      builder: (context, cart, child) {
        final isInCart = cart.isInCart(product.id);
        return IconButton(
          padding: EdgeInsets.zero,
          visualDensity: VisualDensity.compact,
          style: IconButton.styleFrom(
            backgroundColor: isInCart
                ? Theme.of(context).colorScheme.primary
                : Theme.of(context).colorScheme.secondaryContainer,
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8.w(context))),
          ),
          onPressed: product.inStock
              ? () async {
            if (isInCart) {
              Navigator.of(context).pushNamed('/cart');
            } else {
              await cart.addToCart(product);
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('تمت الإضافة إلى السلة'),
                    duration: Duration(seconds: 2),
                  ),
                );
              }
            }
          }
              : null,
          icon: Icon(
            isInCart ? Icons.shopping_cart_checkout : Icons.add_shopping_cart,
            size: 18.sp(context),
            color: isInCart
                ? Theme.of(context).colorScheme.onPrimary
                : Theme.of(context).colorScheme.onSecondaryContainer,
          ),
        );
      },
    );
  }
}