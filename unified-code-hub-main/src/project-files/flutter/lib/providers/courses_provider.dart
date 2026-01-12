import 'package:flutter/material.dart';
import '../models/course.dart';
import '../services/database_helper.dart';

class CoursesProvider extends ChangeNotifier {
  List<Course> _courses = [];
  List<Course> _featuredCourses = [];
  List<String> _categories = [];
  bool _isLoading = false;
  String? _error;

  // Filters
  String? _selectedCategory;
  String? _selectedLevel;
  String? _searchQuery;
  String? _sortBy;

  List<Course> get courses => _courses;
  List<Course> get featuredCourses => _featuredCourses;
  List<String> get categories => _categories;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadCourses() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      _courses = await DatabaseHelper.instance.getAllCourses(
        category: _selectedCategory,
        level: _selectedLevel,
        search: _searchQuery,
        sortBy: _sortBy,
      );
      _featuredCourses = await DatabaseHelper.instance.getAllCourses(featuredOnly: true);
      _categories = await DatabaseHelper.instance.getCategories();
      _isLoading = false;
      notifyListeners();
    } catch (e) {
      _error = e.toString();
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Course?> getCourseById(int id) async {
    return await DatabaseHelper.instance.getCourseById(id);
  }

  void setFilters({String? category, String? level, String? search, String? sortBy}) {
    _selectedCategory = category;
    _selectedLevel = level;
    _searchQuery = search;
    _sortBy = sortBy;
    loadCourses();
  }

  void clearFilters() {
    _selectedCategory = null;
    _selectedLevel = null;
    _searchQuery = null;
    _sortBy = null;
    loadCourses();
  }

  Future<bool> enrollCourse(int userId, int courseId) async {
    try {
      await DatabaseHelper.instance.enrollCourse(userId, courseId);
      return true;
    } catch (e) {
      return false;
    }
  }
}
