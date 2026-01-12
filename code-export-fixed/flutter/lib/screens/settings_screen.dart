import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/theme_provider.dart';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    
    return Scaffold(
      appBar: AppBar(
        title: const Text('الإعدادات'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Appearance section
          _buildSectionHeader('المظهر'),
          Card(
            child: Consumer<ThemeProvider>(
              builder: (context, theme, child) {
                return Column(
                  children: [
                    RadioListTile<ThemeMode>(
                      title: const Text('تلقائي (حسب النظام)'),
                      subtitle: const Text('يتبع إعدادات الجهاز'),
                      value: ThemeMode.system,
                      groupValue: theme.themeMode,
                      onChanged: (value) => theme.setThemeMode(value!),
                    ),
                    RadioListTile<ThemeMode>(
                      title: const Text('الوضع الفاتح'),
                      subtitle: const Text('مظهر فاتح دائماً'),
                      value: ThemeMode.light,
                      groupValue: theme.themeMode,
                      onChanged: (value) => theme.setThemeMode(value!),
                    ),
                    RadioListTile<ThemeMode>(
                      title: const Text('الوضع الداكن'),
                      subtitle: const Text('مظهر داكن دائماً'),
                      value: ThemeMode.dark,
                      groupValue: theme.themeMode,
                      onChanged: (value) => theme.setThemeMode(value!),
                    ),
                  ],
                );
              },
            ),
          ),
          
          const SizedBox(height: 24),
          
          // Notifications section
          _buildSectionHeader('الإشعارات'),
          Card(
            child: Column(
              children: [
                SwitchListTile(
                  title: const Text('إشعارات التطبيق'),
                  subtitle: const Text('استقبال إشعارات عن الطلبات والعروض'),
                  value: true,
                  onChanged: (value) {},
                ),
                SwitchListTile(
                  title: const Text('إشعارات البريد الإلكتروني'),
                  subtitle: const Text('استقبال رسائل عن الطلبات'),
                  value: true,
                  onChanged: (value) {},
                ),
                SwitchListTile(
                  title: const Text('إشعارات العروض'),
                  subtitle: const Text('إشعارات عن العروض والخصومات'),
                  value: false,
                  onChanged: (value) {},
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 24),
          
          // Language section
          _buildSectionHeader('اللغة'),
          Card(
            child: ListTile(
              title: const Text('لغة التطبيق'),
              subtitle: const Text('العربية'),
              trailing: const Icon(Icons.arrow_forward_ios, size: 16),
              onTap: () {
                showModalBottomSheet(
                  context: context,
                  builder: (context) => Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      ListTile(
                        title: const Text('العربية'),
                        leading: const Icon(Icons.check),
                        onTap: () => Navigator.pop(context),
                      ),
                      ListTile(
                        title: const Text('English'),
                        onTap: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
          
          const SizedBox(height: 24),
          
          // Privacy section
          _buildSectionHeader('الخصوصية والأمان'),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.lock_outline),
                  title: const Text('تغيير كلمة المرور'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {},
                ),
                ListTile(
                  leading: const Icon(Icons.fingerprint),
                  title: const Text('تسجيل الدخول البيومتري'),
                  trailing: Switch(value: false, onChanged: (v) {}),
                ),
                ListTile(
                  leading: const Icon(Icons.delete_outline),
                  title: const Text('حذف الحساب'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {},
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 24),
          
          // About section
          _buildSectionHeader('حول التطبيق'),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.info_outline),
                  title: const Text('عن التطبيق'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {},
                ),
                ListTile(
                  leading: const Icon(Icons.description_outlined),
                  title: const Text('الشروط والأحكام'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {},
                ),
                ListTile(
                  leading: const Icon(Icons.privacy_tip_outlined),
                  title: const Text('سياسة الخصوصية'),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {},
                ),
                const ListTile(
                  leading: Icon(Icons.app_settings_alt),
                  title: Text('الإصدار'),
                  trailing: Text('1.0.0'),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8, right: 4),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.bold,
          color: Colors.grey,
        ),
      ),
    );
  }
}
