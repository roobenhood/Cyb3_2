import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../providers/theme_provider.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الإعدادات'),
      ),
      body: ListView(
        padding: EdgeInsets.all(16.w(context)),
        children: [
          // إعدادات المظهر
          Text(
            'المظهر',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.primary,
                ),
          ),
          SizedBox(height: 8.h(context)),
          Card(
            child: Consumer<ThemeProvider>(
              builder: (context, themeProvider, child) {
                return Column(
                  children: [
                    RadioListTile<ThemeMode>(
                      title: const Text('فاتح'),
                      subtitle: const Text('استخدام الوضع الفاتح دائماً'),
                      secondary: const Icon(Icons.light_mode),
                      value: ThemeMode.light,
                      groupValue: themeProvider.themeMode,
                      onChanged: (value) {
                        themeProvider.setThemeMode(value!);
                      },
                    ),
                    RadioListTile<ThemeMode>(
                      title: const Text('داكن'),
                      subtitle: const Text('استخدام الوضع الداكن دائماً'),
                      secondary: const Icon(Icons.dark_mode),
                      value: ThemeMode.dark,
                      groupValue: themeProvider.themeMode,
                      onChanged: (value) {
                        themeProvider.setThemeMode(value!);
                      },
                    ),
                    RadioListTile<ThemeMode>(
                      title: const Text('تلقائي'),
                      subtitle: const Text('مطابقة إعدادات النظام'),
                      secondary: const Icon(Icons.settings_suggest),
                      value: ThemeMode.system,
                      groupValue: themeProvider.themeMode,
                      onChanged: (value) {
                        themeProvider.setThemeMode(value!);
                      },
                    ),
                  ],
                );
              },
            ),
          ),

          SizedBox(height: 24.h(context)),

          // إعدادات الإشعارات
          Text(
            'الإشعارات',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.primary,
                ),
          ),
          SizedBox(height: 8.h(context)),
          Card(
            child: Column(
              children: [
                SwitchListTile(
                  title: const Text('إشعارات الطلبات'),
                  subtitle: const Text('تلقي إشعارات عند تحديث حالة الطلب'),
                  secondary: const Icon(Icons.shopping_bag_outlined),
                  value: true,
                  onChanged: (value) {
                    // TODO: حفظ الإعداد
                  },
                ),
                SwitchListTile(
                  title: const Text('العروض والتخفيضات'),
                  subtitle: const Text('تلقي إشعارات بأحدث العروض'),
                  secondary: const Icon(Icons.local_offer_outlined),
                  value: true,
                  onChanged: (value) {
                    // TODO: حفظ الإعداد
                  },
                ),
                SwitchListTile(
                  title: const Text('منتجات جديدة'),
                  subtitle: const Text('تلقي إشعارات عند إضافة منتجات جديدة'),
                  secondary: const Icon(Icons.new_releases_outlined),
                  value: false,
                  onChanged: (value) {
                    // TODO: حفظ الإعداد
                  },
                ),
              ],
            ),
          ),

          SizedBox(height: 24.h(context)),

          // إعدادات الخصوصية
          Text(
            'الخصوصية والأمان',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.primary,
                ),
          ),
          SizedBox(height: 8.h(context)),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.lock_outline),
                  title: const Text('تغيير كلمة المرور'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    // TODO: صفحة تغيير كلمة المرور
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.privacy_tip_outlined),
                  title: const Text('سياسة الخصوصية'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    // TODO: صفحة سياسة الخصوصية
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.description_outlined),
                  title: const Text('الشروط والأحكام'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    // TODO: صفحة الشروط والأحكام
                  },
                ),
              ],
            ),
          ),

          SizedBox(height: 24.h(context)),

          // إعدادات أخرى
          Text(
            'أخرى',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.primary,
                ),
          ),
          SizedBox(height: 8.h(context)),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.delete_outline),
                  title: const Text('مسح ذاكرة التخزين المؤقت'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    _showClearCacheDialog(context);
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.star_outline),
                  title: const Text('تقييم التطبيق'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    // TODO: فتح صفحة المتجر
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.share_outlined),
                  title: const Text('مشاركة التطبيق'),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    // TODO: مشاركة التطبيق
                  },
                ),
              ],
            ),
          ),

          SizedBox(height: 32.h(context)),

          // معلومات التطبيق
          Center(
            child: Column(
              children: [
                Icon(Icons.shopping_bag, size: 48.sp(context)),
                SizedBox(height: 8.h(context)),
                Text(
                  'متجري',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                Text(
                  'الإصدار 1.0.0',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
          ),

          SizedBox(height: 32.h(context)),
        ],
      ),
    );
  }

  void _showClearCacheDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('مسح ذاكرة التخزين المؤقت'),
        content: const Text('سيتم حذف الصور والبيانات المخزنة مؤقتاً. هل تريد المتابعة؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('إلغاء'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('تم مسح ذاكرة التخزين المؤقت')),
              );
            },
            child: const Text('مسح'),
          ),
        ],
      ),
    );
  }
}
