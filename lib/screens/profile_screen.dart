import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../models/user.dart' as app_user;
import '../providers/auth_provider.dart';
import '../utils/validators.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameController;
  late TextEditingController _phoneController;
  bool _isEditing = false;

  @override
  void initState() {
    super.initState();
    final user = Provider.of<AuthProvider>(context, listen: false).user;
    _nameController = TextEditingController(text: user?.name ?? '');
    _phoneController = TextEditingController(text: user?.phone ?? '');
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  void _syncControllers(app_user.User? user) {
    if (!_isEditing) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          if (_nameController.text != (user?.name ?? '')) {
            _nameController.text = user?.name ?? '';
          }
          if (_phoneController.text != (user?.phone ?? '')) {
            _phoneController.text = user?.phone ?? '';
          }
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<AuthProvider>(
      builder: (context, auth, _) {
        _syncControllers(auth.user);

        return Scaffold(
          appBar: AppBar(
            title: const Text('حسابي'),
            actions: [
              if (auth.isAuthenticated)
                IconButton(
                  icon: Icon(_isEditing ? Icons.close : Icons.edit),
                  onPressed: () {
                    setState(() {
                      _isEditing = !_isEditing;
                      if (!_isEditing) {
                        final user = auth.user;
                        _nameController.text = user?.name ?? '';
                        _phoneController.text = user?.phone ?? '';
                      }
                    });
                  },
                ),
            ],
          ),
          body: !auth.isAuthenticated
              ? _buildGuestView(context)
              : _buildProfileView(context, auth),
        );
      },
    );
  }

  Widget _buildProfileView(BuildContext context, AuthProvider auth) {
    return SingleChildScrollView(
      padding: EdgeInsets.all(16.w(context)),
      child: Form(
        key: _formKey,
        child: Column(
          children: [
            Stack(
              children: [
                CircleAvatar(
                  radius: 60.w(context),
                  backgroundColor: Theme.of(context).colorScheme.primary.withOpacity(0.1),
                  backgroundImage: auth.user?.avatar != null ? NetworkImage(auth.user!.avatar!) : null,
                  child: auth.user?.avatar == null
                      ? Icon(Icons.person, size: 60.sp(context), color: Theme.of(context).primaryColor)
                      : null,
                ),
                if (_isEditing)
                  Positioned(
                    bottom: 0,
                    right: 0,
                    child: CircleAvatar(
                      backgroundColor: Theme.of(context).colorScheme.secondary,
                      child: IconButton(
                        icon: const Icon(Icons.camera_alt, color: Colors.white),
                        onPressed: () {},
                      ),
                    ),
                  ),
              ],
            ),
            SizedBox(height: 24.h(context)),

            TextFormField(
              initialValue: auth.user?.email,
              enabled: false,
              decoration: const InputDecoration(labelText: 'البريد الإلكتروني', prefixIcon: Icon(Icons.email_outlined)),
            ),
            SizedBox(height: 16.h(context)),

            TextFormField(
              controller: _nameController,
              enabled: _isEditing,
              decoration: const InputDecoration(labelText: 'الاسم', prefixIcon: Icon(Icons.person_outline)),
              validator: Validators.validateName,
            ),
            SizedBox(height: 16.h(context)),

            TextFormField(
              controller: _phoneController,
              enabled: _isEditing,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'رقم الهاتف', prefixIcon: Icon(Icons.phone_outlined)),
            ),

            if (_isEditing) ...[
              SizedBox(height: 24.h(context)),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: auth.isLoading ? null : () async {
                    if (!_formKey.currentState!.validate()) return;
                    if (await auth.updateProfile(
                      name: _nameController.text.trim(),
                      phone: _phoneController.text.trim(),
                    )) {
                      setState(() => _isEditing = false);
                      if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('تم التحديث بنجاح')));
                    }
                  },
                  child: auth.isLoading
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('حفظ التغييرات'),
                ),
              ),
            ],

            SizedBox(height: 32.h(context)),

            _buildMenuItem(icon: Icons.receipt_long_outlined, title: 'طلباتي', onTap: () => Navigator.of(context).pushNamed('/orders')),
            _buildMenuItem(icon: Icons.favorite_outline, title: 'المفضلة', onTap: () => Navigator.of(context).pushNamed('/favorites')),
            _buildMenuItem(icon: Icons.settings_outlined, title: 'الإعدادات', onTap: () => Navigator.of(context).pushNamed('/settings')),
            _buildMenuItem(icon: Icons.help_outline, title: 'المساعدة والدعم', onTap: () {}),
            _buildMenuItem(icon: Icons.info_outline, title: 'عن التطبيق', onTap: () {
              showAboutDialog(context: context, applicationName: 'متجري', applicationVersion: '1.0.0', applicationIcon: Icon(Icons.shopping_bag, size: 48.sp(context)));
            }),
            const Divider(),
            _buildMenuItem(icon: Icons.logout, title: 'تسجيل الخروج', color: Colors.red, onTap: () => _showLogoutDialog(context, auth)),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuItem({required IconData icon, required String title, required VoidCallback onTap, Color? color}) {
    return ListTile(
      leading: Icon(icon, color: color),
      title: Text(title, style: TextStyle(color: color)),
      trailing: Icon(Icons.chevron_right, color: color),
      onTap: onTap,
    );
  }

  Widget _buildGuestView(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.person_outline, size: 100.sp(context), color: Colors.grey),
          SizedBox(height: 16.h(context)),
          const Text('لم تسجل الدخول بعد'),
          SizedBox(height: 24.h(context)),
          ElevatedButton(onPressed: () => Navigator.of(context).pushNamed('/login'), child: const Text('تسجيل الدخول')),
        ],
      ),
    );
  }

  void _showLogoutDialog(BuildContext context, AuthProvider auth) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('تسجيل الخروج'),
        content: const Text('هل أنت متأكد من تسجيل الخروج؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')),
          ElevatedButton(
            onPressed: () async {
              await auth.logout();
              if (context.mounted) Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('خروج'),
          ),
        ],
      ),
    );
  }
}
