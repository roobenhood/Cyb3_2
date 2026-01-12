import 'package:flutter/material.dart';
import '../models/category.dart';

/// شريحة التصنيف
class CategoryChip extends StatelessWidget {
  final Category category;
  final bool isSelected;
  final VoidCallback? onTap;

  const CategoryChip({super.key, required this.category, this.isSelected = false, this.onTap});

  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Text(category.name),
      selected: isSelected,
      onSelected: (_) => onTap?.call(),
      avatar: category.icon != null ? Icon(_getIconData(category.icon!), size: 18) : null,
    );
  }

  IconData _getIconData(String iconName) {
    switch (iconName) {
      case 'devices': return Icons.devices;
      case 'checkroom': return Icons.checkroom;
      case 'weekend': return Icons.weekend;
      case 'sports_soccer': return Icons.sports_soccer;
      case 'menu_book': return Icons.menu_book;
      default: return Icons.category;
    }
  }
}
