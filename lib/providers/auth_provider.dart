import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:firebase_auth/firebase_auth.dart' as firebase;
import 'package:google_sign_in/google_sign_in.dart';
import 'package:flutter_facebook_auth/flutter_facebook_auth.dart';
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

  /// مزامنة مع الباك اند - تعمل مع Email/Password و Social Login
  Future<bool> _syncWithBackend(
    firebase.UserCredential cred, {
    String? password,
    bool isSocialLogin = false,
    required String action, // 'login' or 'register'
  }) async {
    try {
      final firebaseUser = cred.user!;
      final idToken = await firebaseUser.getIdToken(true);

      final Map<String, dynamic> authData = {
        'firebase_token': idToken,
        'firebase_uid': firebaseUser.uid,
        'email': firebaseUser.email,
        'name': firebaseUser.displayName ?? firebaseUser.email?.split('@').first ?? 'User',
        'avatar': firebaseUser.photoURL,
      };

      // Only include password for email/password registration
      if (action == 'register' && !isSocialLogin && password != null) {
        authData['password'] = password;
      }

      final response = await _apiService.post('auth.php?action=$action', authData);

      if (response['success'] == true) {
        await _handleSuccessResponse(response['data']);
        return true;
      }
      _error = response['message'] ?? 'فشل الربط مع السيرفر';
      
      if (action == 'login' && response['message']?.contains('not found') == true) {
          _error = 'فشل تسجيل الدخول. الحساب غير مسجل في خوادمنا.';
      }
      return false;
    } catch (e) {
      _error = "خطأ في الاتصال بالسيرفر: ${e.toString()}";
      return false;
    }
  }

  Future<bool> login(String email, String password) async {
    _setLoading(true);
    try {
      final cred = await _firebaseAuth.signInWithEmailAndPassword(
        email: email,
        password: password,
      );
      // Use 'login' action and DO NOT pass the password to the backend sync
      return await _syncWithBackend(cred, action: 'login', isSocialLogin: false);
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
      final cred = await _firebaseAuth.createUserWithEmailAndPassword(
        email: email,
        password: password,
      );
      await cred.user?.updateDisplayName(name);
      // Use 'register' action and pass the password for backend account creation
      return await _syncWithBackend(cred, password: password, action: 'register', isSocialLogin: false);
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
      if (googleUser == null) {
        _error = "تم إلغاء تسجيل الدخول";
        return false;
      }

      final googleAuth = await googleUser.authentication;
      final credential = firebase.GoogleAuthProvider.credential(
        accessToken: googleAuth.accessToken,
        idToken: googleAuth.idToken,
      );

      final userCredential = await _firebaseAuth.signInWithCredential(credential);
      // Backend handles upsert logic (create if not exists, otherwise login)
      return await _syncWithBackend(userCredential, action: 'register', isSocialLogin: true);
    } catch (e) {
      _error = "فشل الدخول عبر Google: ${e.toString()}";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  /// ✅ تسجيل الدخول عبر Facebook - مُصحح بالكامل
  Future<bool> signInWithFacebook() async {
    _setLoading(true);
    try {
      final LoginResult result = await FacebookAuth.instance.login(
        permissions: ['email', 'public_profile'],
      );

      if (result.status == LoginStatus.cancelled) {
        _error = "تم إلغاء تسجيل الدخول";
        return false;
      }

      if (result.status != LoginStatus.success || result.accessToken == null) {
        _error = "فشل تسجيل الدخول عبر Facebook";
        return false;
      }

      final facebookCredential = firebase.FacebookAuthProvider.credential(
        result.accessToken!.tokenString,
      );

      firebase.UserCredential userCredential;
      try {
        userCredential = await _firebaseAuth.signInWithCredential(facebookCredential);
      } on firebase.FirebaseAuthException catch (e) {
        if (e.code == 'account-exists-with-different-credential') {
          _error = "هذا الإيميل مسجل مسبقاً بطريقة أخرى. جرب الدخول بـ Google أو الإيميل وكلمة المرور.";
          return false;
        }
        rethrow;
      }

      // Backend handles upsert logic (create if not exists, otherwise login)
      return await _syncWithBackend(userCredential, action: 'register', isSocialLogin: true);

    } catch (e) {
      _error = "فشل الدخول عبر Facebook: ${e.toString()}";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> updateProfile({required String name, String? phone}) async {
    _setLoading(true);
    try {
      final response = await _apiService.post('auth.php?action=update-profile', {
        'name': name,
        'phone': phone,
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
    try {
      await _firebaseAuth.signOut();
      await GoogleSignIn().signOut();
      await FacebookAuth.instance.logOut();
    } catch (_) {}

    _user = null;
    _token = null;
    _apiService.setToken(null);

    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(AppConfig.tokenKey);
    await prefs.remove(AppConfig.userKey);
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
      case 'invalid-email': return 'البريد الإلكتروني غير صالح.';
      case 'weak-password': return 'كلمة المرور ضعيفة جداً.';
      case 'too-many-requests': return 'محاولات كثيرة. حاول لاحقاً.';
      default: return 'حدث خطأ في المصادقة: ${e.message}';
    }
  }
}
