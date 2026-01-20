import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../conestant/responsiveSize.dart';
import '../providers/auth_provider.dart';
import '../utils/validators.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _showError(BuildContext context, String message) {
    if (!context.mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Theme.of(context).colorScheme.error,
        duration: const Duration(seconds: 4),
      ),
    );
  }

  void _navigateToHome(BuildContext context) {
    if (!context.mounted) return;
    Navigator.of(context).pushNamedAndRemoveUntil('/home', (route) => false);
  }

  Future<void> _handleLogin(BuildContext context) async {
    if (!_formKey.currentState!.validate()) return;

    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final success = await authProvider.login(
      _emailController.text.trim(),
      _passwordController.text,
    );

    if (context.mounted) {
      if (success) {
        _navigateToHome(context);
      } else {
        _showError(context, authProvider.error ?? 'فشل تسجيل الدخول');
      }
    }
  }

  Future<void> _handleSocialLogin(Future<bool> Function() socialLoginFunction) async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    if (authProvider.isLoading) return;

    final success = await socialLoginFunction();

    if (context.mounted) {
      if (success) {
        _navigateToHome(context);
      } else {
        _showError(context, authProvider.error ?? 'فشل تسجيل الدخول');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    // تم وضع الـ Consumer هنا ليشمل الشاشة كاملة مع الحفاظ على التصميم
    return Consumer<AuthProvider>(
      builder: (context, auth, _) {
        return Scaffold(
          body: SafeArea(
            child: SingleChildScrollView(
              padding: EdgeInsets.all(24.w(context)),
              child: Opacity(
                opacity: auth.isLoading ? 0.6 : 1.0,
                child: AbsorbPointer(
                  absorbing: auth.isLoading,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      SizedBox(height: 48.h(context)),
                      Icon(
                        Icons.shopping_bag,
                        size: 80.sp(context),
                        color: Theme.of(context).colorScheme.primary,
                      ),
                      SizedBox(height: 16.h(context)),
                      Text(
                        'مرحباً بك',
                        style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 8.h(context)),
                      Text(
                        'سجّل دخولك للمتابعة',
                        style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                          color: Theme.of(context).textTheme.bodySmall?.color,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: 48.h(context)),
                      Form(
                        key: _formKey,
                        child: Column(
                          children: [
                            TextFormField(
                              controller: _emailController,
                              keyboardType: TextInputType.emailAddress,
                              textInputAction: TextInputAction.next,
                              decoration: const InputDecoration(
                                labelText: 'البريد الإلكتروني',
                                prefixIcon: Icon(Icons.email_outlined),
                              ),
                              validator: Validators.validateEmail,
                            ),
                            SizedBox(height: 16.h(context)),
                            TextFormField(
                              controller: _passwordController,
                              obscureText: _obscurePassword,
                              textInputAction: TextInputAction.done,
                              decoration: InputDecoration(
                                labelText: 'كلمة المرور',
                                prefixIcon: const Icon(Icons.lock_outline),
                                suffixIcon: IconButton(
                                  icon: Icon(_obscurePassword
                                      ? Icons.visibility_outlined
                                      : Icons.visibility_off_outlined),
                                  onPressed: () =>
                                      setState(() => _obscurePassword = !_obscurePassword),
                                ),
                              ),
                              validator: Validators.validatePassword,
                              onFieldSubmitted: (_) => _handleLogin(context),
                            ),
                            SizedBox(height: 8.h(context)),
                            Align(
                              alignment: Alignment.centerLeft,
                              child: TextButton(
                                  onPressed: () {
                                    // إضافة إجراء نسيان كلمة المرور هنا
                                  },
                                  child: const Text('نسيت كلمة المرور؟')),
                            ),
                            SizedBox(height: 24.h(context)),
                            SizedBox(
                              width: double.infinity,
                              child: ElevatedButton(
                                onPressed: () => _handleLogin(context),
                                child: auth.isLoading
                                    ? SizedBox(
                                  height: 20.h(context),
                                  width: 20.w(context),
                                  child: const CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                                    : const Text('تسجيل الدخول'),
                              ),
                            ),
                          ],
                        ),
                      ),
                      SizedBox(height: 32.h(context)),
                      Row(
                        children: [
                          const Expanded(child: Divider()),
                          Padding(
                            padding: EdgeInsets.symmetric(horizontal: 16.w(context)),
                            child: Text('أو', style: Theme.of(context).textTheme.bodySmall),
                          ),
                          const Expanded(child: Divider()),
                        ],
                      ),
                      SizedBox(height: 32.h(context)),
                      // أزرار الدخول الاجتماعي باستخدام auth المتوفر من الـ Consumer
                      OutlinedButton.icon(
                        onPressed: () => _handleSocialLogin(auth.signInWithGoogle),
                        icon: Icon(Icons.g_mobiledata, size: 24.sp(context)),
                        label: const Text('المتابعة مع Google'),
                      ),
                      SizedBox(height: 16.h(context)),
                      OutlinedButton.icon(
                        onPressed: () => _handleSocialLogin(auth.signInWithFacebook),
                        style: OutlinedButton.styleFrom(
                            foregroundColor: const Color(0xFF1877F2)),
                        icon: Icon(Icons.facebook, size: 24.sp(context)),
                        label: const Text('المتابعة مع Facebook'),
                      ),
                      SizedBox(height: 24.h(context)),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text('ليس لديك حساب؟'),
                          TextButton(
                              onPressed: () => Navigator.of(context).pushNamed('/register'),
                              child: const Text('إنشاء حساب')),
                        ],
                      ),
                      TextButton(
                          onPressed: () =>
                              Navigator.of(context).pushReplacementNamed('/home'),
                          child: const Text('تصفح كضيف')),
                      SizedBox(height: 24.h(context)),
                    ],
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}