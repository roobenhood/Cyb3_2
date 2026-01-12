import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../utils/validators.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});
  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('إنشاء حساب')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: ListView(
            children: [
              TextFormField(controller: _nameController, decoration: const InputDecoration(labelText: 'الاسم'), validator: Validators.validateName),
              const SizedBox(height: 16),
              TextFormField(controller: _emailController, decoration: const InputDecoration(labelText: 'البريد الإلكتروني'), validator: Validators.validateEmail),
              const SizedBox(height: 16),
              TextFormField(controller: _passwordController, decoration: const InputDecoration(labelText: 'كلمة المرور'), obscureText: true, validator: Validators.validatePassword),
              const SizedBox(height: 16),
              TextFormField(controller: _confirmPasswordController, decoration: const InputDecoration(labelText: 'تأكيد كلمة المرور'), obscureText: true, validator: (v) => Validators.validateConfirmPassword(v, _passwordController.text)),
              const SizedBox(height: 24),
              Consumer<AuthProvider>(builder: (context, auth, child) => ElevatedButton(
                onPressed: auth.isLoading ? null : () async {
                  if (_formKey.currentState!.validate()) {
                    final success = await auth.register(_nameController.text, _emailController.text, _passwordController.text);
                    if (success && mounted) Navigator.pushReplacementNamed(context, '/home');
                  }
                },
                child: auth.isLoading ? const CircularProgressIndicator() : const Text('إنشاء حساب'),
              )),
            ],
          ),
        ),
      ),
    );
  }
}
