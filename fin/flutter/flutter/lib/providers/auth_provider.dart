import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart' as firebase_auth;
import '../models/user.dart';
import '../services/database_helper.dart';
import '../services/firebase_auth_service.dart';
import '../services/api_service.dart';

/// مزود حالة المصادقة
class AuthProvider extends ChangeNotifier {
  final FirebaseAuthService _authService = FirebaseAuthService();
  final ApiService _apiService = ApiService();

  User? _user;
  bool _isLoading = false;
  String? _error;

  User? get user => _user;
  bool get isLoading => _isLoading;
  bool get isLoggedIn => _user != null || _authService.currentUser != null;
  String? get error => _error;
  firebase_auth.User? get firebaseUser => _authService.currentUser;

  AuthProvider() {
    _init();
  }

  void _init() {
    _authService.authStateChanges.listen((firebaseUser) async {
      if (firebaseUser != null) {
        _user = await DatabaseHelper.instance.getUserByFirebaseUid(firebaseUser.uid);
        notifyListeners();
      } else {
        _user = null;
        notifyListeners();
      }
    });
  }

  // ==================== تسجيل الدخول بالبريد ====================

  Future<bool> loginWithEmail(String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      // محاولة تسجيل الدخول عبر API
      final result = await _apiService.login(email, password);
      if (result['success'] == true) {
        if (result['user'] != null) {
          _user = User.fromJson(result['user']);
        }
        _isLoading = false;
        notifyListeners();
        return true;
      }

      // محاولة تسجيل الدخول عبر Firebase
      final credential = await _authService.signInWithEmail(email, password);
      if (credential != null) {
        _user = await DatabaseHelper.instance.getUserByFirebaseUid(credential.user!.uid);
        if (_user == null) {
          // إنشاء مستخدم محلي
          _user = User(
            firebaseUid: credential.user!.uid,
            name: credential.user!.displayName ?? 'مستخدم',
            email: email,
          );
          final id = await DatabaseHelper.instance.insertUser(_user!);
          _user = _user!.copyWith(id: id);
        }
        _isLoading = false;
        notifyListeners();
        return true;
      }

      // محاولة تسجيل الدخول محلياً
      final localUser = await DatabaseHelper.instance.getUserByEmail(email);
      if (localUser != null && localUser.password == password) {
        _user = localUser;
        _isLoading = false;
        notifyListeners();
        return true;
      }

      _error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
      _isLoading = false;
      notifyListeners();
      return false;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // ==================== إنشاء حساب ====================

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    String? phone,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      // محاولة التسجيل عبر API
      final result = await _apiService.register(
        name: name,
        email: email,
        password: password,
        phone: phone,
      );
      
      if (result['success'] == true) {
        if (result['user'] != null) {
          _user = User.fromJson(result['user']);
        }
        _isLoading = false;
        notifyListeners();
        return true;
      }

      // محاولة التسجيل عبر Firebase
      final credential = await _authService.registerWithEmail(email, password);
      if (credential != null) {
        await _authService.updateProfile(displayName: name);
        
        _user = User(
          firebaseUid: credential.user!.uid,
          name: name,
          email: email,
          phone: phone,
        );
        final id = await DatabaseHelper.instance.insertUser(_user!);
        _user = _user!.copyWith(id: id);
        
        _isLoading = false;
        notifyListeners();
        return true;
      }

      // إنشاء حساب محلي
      _user = User(
        name: name,
        email: email,
        password: password,
        phone: phone,
      );
      final id = await DatabaseHelper.instance.insertUser(_user!);
      _user = _user!.copyWith(id: id);
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // ==================== تسجيل الخروج ====================

  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      await _apiService.logout();
      await _authService.signOut();
    } catch (e) {
      // تجاهل الأخطاء
    }

    _user = null;
    _isLoading = false;
    notifyListeners();
  }

  // ==================== تحديث الملف الشخصي ====================

  Future<bool> updateProfile({
    String? name,
    String? phone,
    String? avatarUrl,
  }) async {
    if (_user == null) return false;

    _isLoading = true;
    notifyListeners();

    try {
      _user = _user!.copyWith(
        name: name ?? _user!.name,
        phone: phone ?? _user!.phone,
        avatarUrl: avatarUrl ?? _user!.avatarUrl,
      );
      await DatabaseHelper.instance.updateUser(_user!);
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // ==================== إعادة تعيين كلمة المرور ====================

  Future<bool> resetPassword(String email) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      await _authService.sendPasswordResetEmail(email);
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
