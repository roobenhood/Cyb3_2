import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:flutter_facebook_auth/flutter_facebook_auth.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/user.dart' as app_user;
import 'database_helper.dart';

class FirebaseAuthService {
  final FirebaseAuth _firebaseAuth = FirebaseAuth.instance;
  final GoogleSignIn _googleSignIn = GoogleSignIn();
  
  // API Base URL - يجب تعديله حسب السيرفر الخاص بك
  static const String apiBaseUrl = 'https://your-api-domain.com/api';

  User? get currentUser => _firebaseAuth.currentUser;

  Stream<User?> get authStateChanges => _firebaseAuth.authStateChanges();

  // ==================== Email/Password Authentication ====================

  Future<UserCredential?> signInWithEmail(String email, String password) async {
    try {
      final credential = await _firebaseAuth.signInWithEmailAndPassword(
        email: email,
        password: password,
      );
      
      // Sync with Web API
      await _syncWithWebApi(credential.user, email, password);
      
      return credential;
    } on FirebaseAuthException catch (e) {
      throw _handleFirebaseError(e);
    }
  }

  Future<UserCredential?> signUpWithEmail(String email, String password, String name) async {
    try {
      final credential = await _firebaseAuth.createUserWithEmailAndPassword(
        email: email,
        password: password,
      );
      
      // Update display name
      await credential.user?.updateDisplayName(name);
      
      // Create user in local database
      await _createLocalUser(credential.user!, name);
      
      // Register with Web API
      await _registerWithWebApi(name, email, password);
      
      return credential;
    } on FirebaseAuthException catch (e) {
      throw _handleFirebaseError(e);
    }
  }

  // ==================== Google Sign In ====================

  Future<UserCredential?> signInWithGoogle() async {
    try {
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      
      if (googleUser == null) {
        throw 'تم إلغاء تسجيل الدخول';
      }

      final GoogleSignInAuthentication googleAuth = await googleUser.authentication;

      final credential = GoogleAuthProvider.credential(
        accessToken: googleAuth.accessToken,
        idToken: googleAuth.idToken,
      );

      final userCredential = await _firebaseAuth.signInWithCredential(credential);
      
      // Create/update local user
      await _createLocalUser(
        userCredential.user!,
        googleUser.displayName ?? 'مستخدم Google',
      );
      
      return userCredential;
    } on FirebaseAuthException catch (e) {
      throw _handleFirebaseError(e);
    } catch (e) {
      throw 'فشل تسجيل الدخول بواسطة Google: $e';
    }
  }

  // ==================== Facebook Sign In ====================

  Future<UserCredential?> signInWithFacebook() async {
    try {
      final LoginResult result = await FacebookAuth.instance.login();
      
      if (result.status == LoginStatus.cancelled) {
        throw 'تم إلغاء تسجيل الدخول';
      }
      
      if (result.status == LoginStatus.failed) {
        throw 'فشل تسجيل الدخول بواسطة Facebook';
      }

      final OAuthCredential credential = FacebookAuthProvider.credential(
        result.accessToken!.tokenString,
      );

      final userCredential = await _firebaseAuth.signInWithCredential(credential);
      
      // Get Facebook user data
      final userData = await FacebookAuth.instance.getUserData();
      
      // Create/update local user
      await _createLocalUser(
        userCredential.user!,
        userData['name'] ?? 'مستخدم Facebook',
      );
      
      return userCredential;
    } on FirebaseAuthException catch (e) {
      throw _handleFirebaseError(e);
    } catch (e) {
      throw 'فشل تسجيل الدخول بواسطة Facebook: $e';
    }
  }

  // ==================== Sign Out ====================

  Future<void> signOut() async {
    await Future.wait([
      _firebaseAuth.signOut(),
      _googleSignIn.signOut(),
      FacebookAuth.instance.logOut(),
    ]);
  }

  // ==================== Password Reset ====================

  Future<void> sendPasswordResetEmail(String email) async {
    try {
      await _firebaseAuth.sendPasswordResetEmail(email: email);
    } on FirebaseAuthException catch (e) {
      throw _handleFirebaseError(e);
    }
  }

  // ==================== Helper Methods ====================

  Future<void> _createLocalUser(User firebaseUser, String name) async {
    final existingUser = await DatabaseHelper.instance.getUserByFirebaseUid(firebaseUser.uid);
    
    if (existingUser == null) {
      final newUser = app_user.User(
        firebaseUid: firebaseUser.uid,
        name: name,
        email: firebaseUser.email ?? '',
        avatarUrl: firebaseUser.photoURL,
      );
      await DatabaseHelper.instance.createUser(newUser);
    }
  }

  Future<void> _syncWithWebApi(User? firebaseUser, String email, String password) async {
    if (firebaseUser == null) return;
    
    try {
      final response = await http.post(
        Uri.parse('$apiBaseUrl/auth.php?action=login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // يمكن تخزين الـ token للاستخدام لاحقاً
        if (data['success'] == true && data['data']['token'] != null) {
          await DatabaseHelper.instance.setSetting('api_token', data['data']['token']);
        }
      }
    } catch (e) {
      // تجاهل الأخطاء - يمكن المتابعة بدون API
      print('API sync failed: $e');
    }
  }

  Future<void> _registerWithWebApi(String name, String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$apiBaseUrl/auth.php?action=register'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
        }),
      );
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true && data['data']['token'] != null) {
          await DatabaseHelper.instance.setSetting('api_token', data['data']['token']);
        }
      }
    } catch (e) {
      print('API registration failed: $e');
    }
  }

  String _handleFirebaseError(FirebaseAuthException e) {
    switch (e.code) {
      case 'user-not-found':
        return 'لا يوجد حساب بهذا البريد الإلكتروني';
      case 'wrong-password':
        return 'كلمة المرور غير صحيحة';
      case 'email-already-in-use':
        return 'البريد الإلكتروني مستخدم بالفعل';
      case 'weak-password':
        return 'كلمة المرور ضعيفة جداً';
      case 'invalid-email':
        return 'البريد الإلكتروني غير صالح';
      case 'user-disabled':
        return 'تم تعطيل هذا الحساب';
      case 'too-many-requests':
        return 'محاولات كثيرة جداً، حاول لاحقاً';
      case 'operation-not-allowed':
        return 'هذه العملية غير مسموحة';
      case 'network-request-failed':
        return 'فشل الاتصال بالشبكة';
      default:
        return 'حدث خطأ: ${e.message}';
    }
  }
}
