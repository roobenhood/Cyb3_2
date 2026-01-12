import 'package:permission_handler/permission_handler.dart';

/// مساعد الصلاحيات
class PermissionHelper {
  /// طلب الصلاحيات الأساسية عند بداية التطبيق
  static Future<void> requestInitialPermissions() async {
    // طلب صلاحية الإشعارات
    await Permission.notification.request();
  }

  /// طلب صلاحية الكاميرا
  static Future<bool> requestCameraPermission() async {
    final status = await Permission.camera.request();
    return status.isGranted;
  }

  /// طلب صلاحية الصور
  static Future<bool> requestPhotosPermission() async {
    final status = await Permission.photos.request();
    if (status.isGranted) return true;
    
    // للأجهزة القديمة
    final storageStatus = await Permission.storage.request();
    return storageStatus.isGranted;
  }

  /// طلب صلاحية التخزين
  static Future<bool> requestStoragePermission() async {
    final status = await Permission.storage.request();
    return status.isGranted;
  }

  /// طلب صلاحية الإشعارات
  static Future<bool> requestNotificationPermission() async {
    final status = await Permission.notification.request();
    return status.isGranted;
  }

  /// طلب صلاحية الموقع
  static Future<bool> requestLocationPermission() async {
    final status = await Permission.location.request();
    return status.isGranted;
  }

  /// التحقق من صلاحية
  static Future<bool> checkPermission(Permission permission) async {
    final status = await permission.status;
    return status.isGranted;
  }

  /// فتح إعدادات التطبيق
  static Future<bool> openSettings() async {
    return await openAppSettings();
  }
}
