import 'package:firebase_auth/firebase_auth.dart';

class FirebaseAuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;

  // الحصول على المستخدم الحالي
  User? get currentUser => _auth.currentUser;

  // stream للاستماع لتغييرات حالة المصادقة
  Stream<User?> get authStateChanges => _auth.authStateChanges();

  // تسجيل الدخول بالإيميل وكلمة المرور
  Future<UserCredential?> signInWithEmail(String email, String password) async {
    try {
      return await _auth.signInWithEmailAndPassword(
        email: email,
        password: password,
      );
    } on FirebaseAuthException catch (e) {
      throw _handleAuthException(e);
    }
  }

  // إنشاء حساب جديد
  Future<UserCredential?> registerWithEmail(
    String email,
    String password,
  ) async {
    try {
      return await _auth.createUserWithEmailAndPassword(
        email: email,
        password: password,
      );
    } on FirebaseAuthException catch (e) {
      throw _handleAuthException(e);
    }
  }

  // تسجيل الخروج
  Future<void> signOut() async {
    await _auth.signOut();
  }

  // إرسال إيميل إعادة تعيين كلمة المرور
  Future<void> sendPasswordResetEmail(String email) async {
    try {
      await _auth.sendPasswordResetEmail(email: email);
    } on FirebaseAuthException catch (e) {
      throw _handleAuthException(e);
    }
  }

  // تحديث الملف الشخصي
  Future<void> updateDisplayName(String name) async {
    await currentUser?.updateDisplayName(name);
  }

  // التحقق من الإيميل
  Future<void> sendEmailVerification() async {
    await currentUser?.sendEmailVerification();
  }

  // معالجة أخطاء Firebase
  String _handleAuthException(FirebaseAuthException e) {
    switch (e.code) {
      case 'user-not-found':
        return 'لا يوجد مستخدم بهذا البريد الإلكتروني';
      case 'wrong-password':
        return 'كلمة المرور غير صحيحة';
      case 'email-already-in-use':
        return 'البريد الإلكتروني مستخدم بالفعل';
      case 'weak-password':
        return 'كلمة المرور ضعيفة جداً';
      case 'invalid-email':
        return 'البريد الإلكتروني غير صالح';
      default:
        return 'حدث خطأ: ${e.message}';
    }
  }
}
