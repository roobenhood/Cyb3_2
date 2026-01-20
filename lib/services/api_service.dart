import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:cookie_jar/cookie_jar.dart';
import 'package:dio_cookie_manager/dio_cookie_manager.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;

  late Dio _dio;
  String? _token;
  final CookieJar _cookieJar = CookieJar();

  ApiService._internal() {
    BaseOptions options = BaseOptions(
      baseUrl: AppConfig.apiBaseUrl,
      connectTimeout: const Duration(milliseconds: AppConfig.connectionTimeout),
      receiveTimeout: const Duration(milliseconds: AppConfig.receiveTimeout),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36',
      },
    );

    _dio = Dio(options);
    _dio.interceptors.add(CookieManager(_cookieJar));

    if (kDebugMode) {
      _dio.interceptors.add(LogInterceptor(responseBody: true, requestBody: true));
    }
  }

  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(AppConfig.tokenKey);
    if (_token != null) {
      _dio.options.headers['Authorization'] = 'Bearer $_token';
    }
  }

  void setToken(String? token) {
    _token = token;
    if (token != null) {
      _dio.options.headers['Authorization'] = 'Bearer $_token';
    } else {
      _dio.options.headers.remove('Authorization');
    }
  }

  // --- GET METHOD (RESTORED) ---
  Future<Map<String, dynamic>> get(String endpoint) async {
    try {
      final response = await _dio.get(endpoint);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  // --- POST METHOD ---
  Future<Map<String, dynamic>> post(String endpoint, Map<String, dynamic> data) async {
    try {
      final response = await _dio.post(endpoint, data: jsonEncode(data));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  // --- PUT METHOD (RESTORED) ---
  Future<Map<String, dynamic>> put(String endpoint, Map<String, dynamic> data) async {
    try {
      final response = await _dio.put(endpoint, data: jsonEncode(data));
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  // --- DELETE METHOD (RESTORED) ---
  Future<Map<String, dynamic>> delete(String endpoint) async {
    try {
      final response = await _dio.delete(endpoint);
      return _handleResponse(response);
    } on DioException catch (e) {
      return _handleDioError(e);
    }
  }

  Map<String, dynamic> _handleResponse(Response response) {
    if (response.data is String) {
      try {
        final decoded = jsonDecode(response.data);
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
        // if the response is not a map, wrap it
        return {'success': true, 'data': decoded};
      } catch (e) {
        // This happens if the string is not valid JSON (e.g. a raw PHP error message)
        return {'success': false, 'message': 'Failed to parse server response.', 'data': response.data};
      }
    } else if (response.data is Map<String, dynamic>) {
      return response.data;
    } else {
      return {'success': false, 'message': 'Received unexpected data type from server.'};
    }
  }

  Map<String, dynamic> _handleDioError(DioException e) {
    if (e.type == DioExceptionType.unknown || e.error is FormatException) {
      String responseBody = e.response?.data?.toString() ?? 'No response body available. Check PHP logs.';
      return {
        'success': false,
        // The real PHP error will now be in the message!
        'message': 'PHP_ERROR: $responseBody'
      };
    }

    if (e.response != null) {
      return {
        'success': false,
        'message': e.response?.data?['message'] ?? 'An error occurred on the server. Status: ${e.response?.statusCode}'
      };
    } else {
      return {'success': false, 'message': 'Network connection error: Please check your internet connection.'};
    }
  }
}