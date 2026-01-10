import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  // غير هذا الرابط إلى رابط السيرفر الخاص بك
  static const String baseUrl = 'https://your-domain.com/api';

  // تسجيل الدخول
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );
    return jsonDecode(response.body);
  }

  // تسجيل حساب جديد
  static Future<Map<String, dynamic>> register(
    String name,
    String email,
    String password,
  ) async {
    final response = await http.post(
      Uri.parse('$baseUrl/register.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'name': name,
        'email': email,
        'password': password,
      }),
    );
    return jsonDecode(response.body);
  }

  // جلب الكورسات
  static Future<List<dynamic>> getCourses() async {
    final response = await http.get(Uri.parse('$baseUrl/courses.php'));
    final data = jsonDecode(response.body);
    return data['courses'] ?? [];
  }

  // جلب تفاصيل كورس
  static Future<Map<String, dynamic>> getCourseDetails(int courseId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/course_details.php?id=$courseId'),
    );
    return jsonDecode(response.body);
  }

  // شراء كورس
  static Future<Map<String, dynamic>> purchaseCourse(
    String token,
    int courseId,
  ) async {
    final response = await http.post(
      Uri.parse('$baseUrl/purchase.php'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({'course_id': courseId}),
    );
    return jsonDecode(response.body);
  }

  // جلب كورسات المستخدم
  static Future<List<dynamic>> getUserCourses(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/user_courses.php'),
      headers: {'Authorization': 'Bearer $token'},
    );
    final data = jsonDecode(response.body);
    return data['courses'] ?? [];
  }

  // تحديث الملف الشخصي
  static Future<Map<String, dynamic>> updateProfile(
    String token,
    Map<String, dynamic> data,
  ) async {
    final response = await http.post(
      Uri.parse('$baseUrl/update_profile.php'),
      headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode(data),
    );
    return jsonDecode(response.body);
  }
}
