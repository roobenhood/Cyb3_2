import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});
  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    return Scaffold(
      appBar: AppBar(title: const Text('الملف الشخصي')),
      body: user == null ? const Center(child: Text('يرجى تسجيل الدخول')) : ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const CircleAvatar(radius: 50, child: Icon(Icons.person, size: 50)),
          const SizedBox(height: 16),
          Text(user.name, textAlign: TextAlign.center, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
          Text(user.email, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600)),
          const SizedBox(height: 24),
          ListTile(leading: const Icon(Icons.school), title: const Text('كورساتي'), onTap: () {}),
          ListTile(leading: const Icon(Icons.favorite), title: const Text('المفضلة'), onTap: () {}),
          ListTile(leading: const Icon(Icons.settings), title: const Text('الإعدادات'), onTap: () {}),
          ListTile(leading: const Icon(Icons.logout, color: Colors.red), title: const Text('تسجيل الخروج', style: TextStyle(color: Colors.red)), onTap: () {
            context.read<AuthProvider>().logout();
            Navigator.pushReplacementNamed(context, '/login');
          }),
        ],
      ),
    );
  }
}
