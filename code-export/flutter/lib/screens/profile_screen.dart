import 'package:flutter/material.dart';
import '../services/firebase_auth_service.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _authService = FirebaseAuthService();

  @override
  Widget build(BuildContext context) {
    final user = _authService.currentUser;

    return Scaffold(
      appBar: AppBar(
        title: const Text('الملف الشخصي'),
        backgroundColor: const Color(0xFF667eea),
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF667eea), Color(0xFF764ba2)],
                ),
              ),
              child: Column(
                children: [
                  CircleAvatar(
                    radius: 50,
                    backgroundColor: Colors.white,
                    child: Text(
                      (user?.displayName ?? 'U')[0].toUpperCase(),
                      style: const TextStyle(
                        fontSize: 40,
                        color: Color(0xFF667eea),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    user?.displayName ?? 'المستخدم',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    user?.email ?? 'email@example.com',
                    style: const TextStyle(color: Colors.white70),
                  ),
                ],
              ),
            ),

            // Stats
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(child: _buildStatCard('الكورسات', '5')),
                  const SizedBox(width: 16),
                  Expanded(child: _buildStatCard('الشهادات', '3')),
                  const SizedBox(width: 16),
                  Expanded(child: _buildStatCard('ساعات التعلم', '42')),
                ],
              ),
            ),

            // Menu Items
            const Divider(),
            _buildMenuItem(Icons.book, 'كورساتي', () {}),
            _buildMenuItem(Icons.card_membership, 'الشهادات', () {}),
            _buildMenuItem(Icons.favorite, 'المفضلة', () {}),
            _buildMenuItem(Icons.payment, 'طرق الدفع', () {}),
            _buildMenuItem(Icons.settings, 'الإعدادات', () {}),
            _buildMenuItem(Icons.help, 'المساعدة', () {}),
            const Divider(),
            _buildMenuItem(
              Icons.logout,
              'تسجيل الخروج',
              () async {
                await _authService.signOut();
                Navigator.pushReplacementNamed(context, '/login');
              },
              color: Colors.red,
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String label, String value) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.2),
            blurRadius: 8,
          ),
        ],
      ),
      child: Column(
        children: [
          Text(
            value,
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Color(0xFF667eea),
            ),
          ),
          const SizedBox(height: 4),
          Text(label, style: const TextStyle(color: Colors.grey)),
        ],
      ),
    );
  }

  Widget _buildMenuItem(IconData icon, String title, VoidCallback onTap,
      {Color? color}) {
    return ListTile(
      leading: Icon(icon, color: color ?? const Color(0xFF667eea)),
      title: Text(title, style: TextStyle(color: color)),
      trailing: const Icon(Icons.chevron_left),
      onTap: onTap,
    );
  }
}
