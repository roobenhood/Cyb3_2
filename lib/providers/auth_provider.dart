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

  // مزامنة مع الباك إند - للتسجيل العادي بكلمة مرور
  Future<bool> _syncWithBackend(firebase.UserCredential cred, {required String password}) async {
    try {
      final Map<String, dynamic> authData = {
        'email': cred.user!.email,
        'name': cred.user!.displayName ?? cred.user!.email?.split('@').first ?? 'User',
        'password': password,
      };

      final response = await _apiService.post('auth.php?action=register', authData);

      if (response['success'] == true) {
        await _handleSuccessResponse(response['data']);
        return true;
      }

      // إذا كان الحساب موجود، حاول تسجيل الدخول
      if (response['message']?.toString().contains('موجود') ?? false) {
        return await _loginToBackend(cred.user!.email!, password);
      }

      _error = response['message'] ?? 'فشل الربط مع السيرفر';
      return false;
    } catch (e) {
      _error = "خطأ في الاتصال بالسيرفر: $e";
      return false;
    }
  }

  // مزامنة مع الباك إند - للتسجيل عبر Social Login (بدون كلمة مرور)
  Future<bool> _syncSocialWithBackend(firebase.UserCredential cred, String provider) async {
    try {
      final Map<String, dynamic> authData = {
        'email': cred.user!.email,
        'name': cred.user!.displayName ?? cred.user!.email?.split('@').first ?? 'User',
        'provider': provider,
        'firebase_uid': cred.user!.uid,
      };

      final response = await _apiService.post('auth.php?action=social_login', authData);

      if (response['success'] == true) {
        await _handleSuccessResponse(response['data']);
        return true;
      }

      _error = response['message'] ?? 'فشل الربط مع السيرفر';
      return false;
    } catch (e) {
      _error = "خطأ في الاتصال بالسيرفر: $e";
      return false;
    }
  }

  // تسجيل دخول للباك إند
  Future<bool> _loginToBackend(String email, String password) async {
    try {
      final response = await _apiService.post('auth.php?action=login', {
        'email': email,
        'password': password,
      });

      if (response['success'] == true) {
        await _handleSuccessResponse(response['data']);
        return true;
      }
      _error = response['message'] ?? 'فشل تسجيل الدخول';
      return false;
    } catch (e) {
      _error = "خطأ في الاتصال بالسيرفر";
      return false;
    }
  }

  // تسجيل الدخول بالإيميل وكلمة المرور
  Future<bool> login(String email, String password) async {
    _setLoading(true);
    try {
      // تسجيل الدخول في Firebase
      await _firebaseAuth.signInWithEmailAndPassword(email: email, password: password);
      // تسجيل الدخول في الباك إند
      return await _loginToBackend(email, password);
    } on firebase.FirebaseAuthException catch (e) {
      _error = _mapFirebaseError(e);
      return false;
    } catch (e) {
      _error = "حدث خطأ غير متوقع";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  // تسجيل حساب جديد بالإيميل وكلمة المرور
  Future<bool> register(String name, String email, String password) async {
    _setLoading(true);
    try {
      final cred = await _firebaseAuth.createUserWithEmailAndPassword(email: email, password: password);
      await cred.user?.updateDisplayName(name);
      return await _syncWithBackend(cred, password: password);
    } on firebase.FirebaseAuthException catch (e) {
      _error = _mapFirebaseError(e);
      return false;
    } catch (e) {
      _error = "حدث خطأ غير متوقع";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  // تسجيل الدخول عبر Google
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
      return await _syncSocialWithBackend(userCredential, 'google');
    } on firebase.FirebaseAuthException catch (e) {
      _error = _handleSocialAuthError(e, 'google');
      return false;
    } catch (e) {
      _error = "فشل الدخول عبر Google: $e";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  // تسجيل الدخول عبر Facebook
  Future<bool> signInWithFacebook() async {
    _setLoading(true);
    try {
      // تسجيل الدخول عبر Facebook SDK
      final LoginResult result = await FacebookAuth.instance.login(
        permissions: ['email', 'public_profile'],
      );

      if (result.status == LoginStatus.cancelled) {
        _error = "تم إلغاء تسجيل الدخول";
        return false;
      }

      if (result.status != LoginStatus.success) {
        _error = "فشل تسجيل الدخول عبر Facebook: ${result.message}";
        return false;
      }

      final accessToken = result.accessToken!;

      // إنشاء credential لـ Firebase
      final credential = firebase.FacebookAuthProvider.credential(accessToken.tokenString);

      try {
        // محاولة تسجيل الدخول في Firebase
        final userCredential = await _firebaseAuth.signInWithCredential(credential);
        return await _syncSocialWithBackend(userCredential, 'facebook');
      } on firebase.FirebaseAuthException catch (e) {
        // معالجة خطأ الحساب المرتبط بمزود آخر
        if (e.code == 'account-exists-with-different-credential') {
          return await _handleAccountExistsError(e, credential);
        }
        _error = _handleSocialAuthError(e, 'facebook');
        return false;
      }
    } catch (e) {
      _error = "فشل الدخول عبر Facebook: $e";
      return false;
    } finally {
      _setLoading(false);
    }
  }

  // معالجة خطأ الحساب الموجود مسبقاً بمزود مختلف
  Future<bool> _handleAccountExistsError(
      firebase.FirebaseAuthException e,
      firebase.AuthCredential newCredential,
      ) async {
    final email = e.email;
    if (email == null) {
      _error = "لم يتم العثور على البريد الإلكتروني";
      return false;
    }

    try {
      // الحصول على قائمة المزودات المرتبطة بالبريد
      final methods = await _firebaseAuth.fetchSignInMethodsForEmail(email);

      if (methods.contains('google.com')) {
        // ربط الحساب عبر Google
        final googleUser = await GoogleSignIn().signIn();
        if (googleUser == null) {
          _error = "يرجى تسجيل الدخول عبر Google أولاً ثم ربط حساب Facebook";
          return false;
        }

        final googleAuth = await googleUser.authentication;
        final googleCredential = firebase.GoogleAuthProvider.credential(
          accessToken: googleAuth.accessToken,
          idToken: googleAuth.idToken,
        );

        final userCredential = await _firebaseAuth.signInWithCredential(googleCredential);
        // ربط Facebook credential بالحساب الحالي
        await userCredential.user?.linkWithCredential(newCredential);
        return await _syncSocialWithBackend(userCredential, 'facebook');
      } else if (methods.contains('password')) {
        _error = "هذا البريد مسجل بكلمة مرور. يرجى تسجيل الدخول بالبريد وكلمة المرور أولاً.";
        return false;
      } else {
        _error = "هذا البريد مرتبط بحساب آخر. يرجى استخدام طريقة تسجيل دخول مختلفة.";
        return false;
      }
    } catch (e) {
      _error = "فشل ربط الحسابات: $e";
      return false;
    }
  }

  // معالجة أخطاء Social Auth
  String _handleSocialAuthError(firebase.FirebaseAuthException e, String provider) {
    switch (e.code) {
      case 'account-exists-with-different-credential':
        return 'هذا البريد مرتبط بحساب آخر. جرب تسجيل الدخول بطريقة مختلفة.';
      case 'invalid-credential':
        return 'بيانات الاعتماد غير صالحة. يرجى المحاولة مرة أخرى.';
      case 'user-disabled':
        return 'تم تعطيل هذا الحساب.';
      case 'operation-not-allowed':
        return 'تسجيل الدخول عبر $provider غير مفعل.';
      default:
        return 'فشل تسجيل الدخول عبر $provider: ${e.message}';
    }
  }

  // تحديث الملف الشخصي
  Future<bool> updateProfile({required String name, String? phone}) async {
    _setLoading(true);
    try {
      final response = await _apiService.post('auth.php?action=update', {
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
    } catch (e) {
      _error = "فشل تحديث البيانات";
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

  void clearError() {
    _error = null;
    notifyListeners();
  }

  String _mapFirebaseError(firebase.FirebaseAuthException e) {
    switch (e.code) {
      case 'user-not-found':
        return 'الحساب غير موجود.';
      case 'wrong-password':
        return 'كلمة المرور خاطئة.';
      case 'email-already-in-use':
        return 'البريد مستخدم بالفعل.';
      case 'weak-password':
        return 'كلمة المرور ضعيفة جداً.';
      case 'invalid-email':
        return 'البريد الإلكتروني غير صالح.';
      case 'too-many-requests':
        return 'محاولات كثيرة. يرجى المحاولة لاحقاً.';
      default:
        return 'حدث خطأ في المصادقة: ${e.message}';
    }
  }
}
