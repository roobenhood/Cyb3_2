import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:firebase_auth/firebase_auth.dart' as firebase;
import 'package:google_sign_in/google_sign_in.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../models/user.dart' as app_user;
import '../services/api_service.dart';

class AuthProvider with ChangeNotifier {
  app_user.User? _user;
  String? _token;
  bool _isLoading = false;
  String? _error;
  final firebase.FirebaseAuth _firebaseAuth = firebase.FirebaseAuth.instance;
  final ApiService _apiService = ApiService();

  app_user.User? get user => _user;
  String? get token => _token;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _token != null && _user != null;

  AuthProvider() {
    _loadUserFromStorage();
  }

  Future<void> _loadUserFromStorage() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(AppConfig.tokenKey);
    final userDataString = prefs.getString(AppConfig.userKey);

    if (_token != null && userDataString != null) {
      try {
        _user = app_user.User.fromJson(jsonDecode(userDataString));
        _apiService.setToken(_token);
        notifyListeners();
      } catch (e) {
        await logout();
      }
    }
  }

  Future<bool> _syncWithBackend(firebase.UserCredential cred, {String? password}) async {
    try {
      final idToken = await cred.user!.getIdToken(true);
      final Map<String, dynamic> authData = {
        'token': idToken,
        'email': cred.user!.email,
        'name': cred.user!.displayName ?? cred.user!.email?.split('@').first ?? 'User',
      };
      if (password != null) authData['password'] = password;

      final response = await _apiService.post('auth.php?action=register', authData);

      if (response['success'] == true) {
        await _handleSuccessResponse(response['data']);
        return true;
      }
      _error = response['message'] ?? 'فشل الربط مع السيرفر';
      return false;
    } catch (e) {
      _error = "خطأ في الاتصال بالسيرفر";
      return false;
    }
  }

  Future<bool> login(String email, String password) async {
    _setLoading(true);
    try {
      final cred = await _firebaseAuth.signInWithEmailAndPassword(email: email, password: password);
      return await _syncWithBackend(cred, password: password);
    } on firebase.FirebaseAuthException catch (e) {
      _error = _mapFirebaseError(e);
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> register(String name, String email, String password) async {
    _setLoading(true);
    try {
      final cred = await _firebaseAuth.createUserWithEmailAndPassword(email: email, password: password);
      await cred.user?.updateDisplayName(name);
      return await _syncWithBackend(cred, password: password);
    } on firebase.FirebaseAuthException catch (e) {
      _error = _mapFirebaseError(e);
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> signInWithGoogle() async {
    _setLoading(true);
    try {
      final googleUser = await GoogleSignIn().signIn();
      if (googleUser == null) return false;
      final googleAuth = await googleUser.authentication;
      final credential = firebase.GoogleAuthProvider.credential(
          accessToken: googleAuth.accessToken, idToken: googleAuth.idToken);
      final userCredential = await _firebaseAuth.signInWithCredential(credential);
      return await _syncWithBackend(userCredential);
    } catch (e) {
      _error = "فشل الدخول عبر Google";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  // دالة الفيسبوك المفقودة
  Future<bool> signInWithFacebook() async {
    _setLoading(true);
    try {
      // ملاحظة: تتطلب إعداد Facebook Login SDK في المشروع
      _error = "تسجيل الدخول عبر Facebook غير مفعل حالياً في الإعدادات";
      return false;
    } catch (e) {
      _error = "فشل الدخول عبر Facebook";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> updateProfile({required String name, String? phone, String? address}) async {
    _setLoading(true);
    try {
      final response = await _apiService.post('auth.php?action=update-profile', {
        'name': name, 'phone': phone, 'address': address,
      });
      if (response['success'] == true) {
        _user = app_user.User.fromJson(response['data']);
        await _saveUserToStorage();
        notifyListeners();
        return true;
      }
      _error = response['message'];
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<void> _handleSuccessResponse(Map<String, dynamic> data) async {
    _token = data['token'];
    _user = app_user.User.fromJson(data['user']);
    _apiService.setToken(_token);
    await _saveUserToStorage();
    notifyListeners();
  }

  Future<void> _saveUserToStorage() async {
    final prefs = await SharedPreferences.getInstance();
    if (_token != null) await prefs.setString(AppConfig.tokenKey, _token!);
    if (_user != null) await prefs.setString(AppConfig.userKey, jsonEncode(_user!.toJson()));
  }

  Future<void> logout() async {
    await _firebaseAuth.signOut();
    _user = null; _token = null;
    _apiService.setToken(null);
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    notifyListeners();
  }

  void _setLoading(bool value) {
    _isLoading = value;
    if (value) _error = null;
    notifyListeners();
  }

  String _mapFirebaseError(firebase.FirebaseAuthException e) {
    switch (e.code) {
      case 'user-not-found': return 'الحساب غير موجود.';
      case 'wrong-password': return 'كلمة المرور خاطئة.';
      case 'email-already-in-use': return 'البريد مستخدم بالفعل.';
      default: return 'حدث خطأ في المصادقة.';
    }
  }
}