import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart' as firebase_auth;
import '../models/user.dart';
import '../services/database_helper.dart';
import '../services/firebase_auth_service.dart';

class AuthProvider extends ChangeNotifier {
  final FirebaseAuthService _authService = FirebaseAuthService();
  
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

  // ==================== Email/Password Login ====================

  Future<bool> loginWithEmail(String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final credential = await _authService.signInWithEmail(email, password);
      if (credential != null) {
        _user = await DatabaseHelper.instance.getUserByFirebaseUid(credential.user!.uid);
        _isLoading = false;
        notifyListeners();
        return true;
      }
      _error = 'فشل تسجيل الدخول';
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

  Future<bool> registerWithEmail(String name, String email, String password) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final credential = await _authService.signUpWithEmail(email, password, name);
      if (credential != null) {
        _user = await DatabaseHelper.instance.getUserByFirebaseUid(credential.user!.uid);
        _isLoading = false;
        notifyListeners();
        return true;
      }
      _error = 'فشل إنشاء الحساب';
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

  // ==================== Google Login ====================

  Future<bool> loginWithGoogle() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final credential = await _authService.signInWithGoogle();
      if (credential != null) {
        _user = await DatabaseHelper.instance.getUserByFirebaseUid(credential.user!.uid);
        _isLoading = false;
        notifyListeners();
        return true;
      }
      _error = 'فشل تسجيل الدخول بواسطة Google';
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

  // ==================== Facebook Login ====================

  Future<bool> loginWithFacebook() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final credential = await _authService.signInWithFacebook();
      if (credential != null) {
        _user = await DatabaseHelper.instance.getUserByFirebaseUid(credential.user!.uid);
        _isLoading = false;
        notifyListeners();
        return true;
      }
      _error = 'فشل تسجيل الدخول بواسطة Facebook';
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

  // ==================== Logout ====================

  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();
    
    await _authService.signOut();
    _user = null;
    
    _isLoading = false;
    notifyListeners();
  }

  // ==================== Password Reset ====================

  Future<bool> sendPasswordReset(String email) async {
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

  // ==================== Profile Update ====================

  Future<void> updateProfile(User updatedUser) async {
    await DatabaseHelper.instance.updateUser(updatedUser);
    _user = updatedUser;
    notifyListeners();
  }

  void clearError() {
    _error = null;
    notifyListeners();
  }
}
