import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import '../models/course.dart';
import '../models/user.dart';

class DatabaseHelper {
  static final DatabaseHelper instance = DatabaseHelper._init();
  static Database? _database;

  DatabaseHelper._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('courses_app.db');
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);
    return await openDatabase(path, version: 1, onCreate: _createDB);
  }

  Future<void> _createDB(Database db, int version) async {
    await db.execute('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, firebase_uid TEXT UNIQUE, name TEXT NOT NULL, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, phone TEXT, avatar_url TEXT, address TEXT, city TEXT, country TEXT, role TEXT DEFAULT "student", is_active INTEGER DEFAULT 1, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    await db.execute('CREATE TABLE courses (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT, instructor_id INTEGER, instructor_name TEXT, price REAL DEFAULT 0, discount_price REAL, rating REAL DEFAULT 0, students_count INTEGER DEFAULT 0, duration TEXT, category TEXT, level TEXT DEFAULT "beginner", language TEXT DEFAULT "ar", image_url TEXT, preview_video_url TEXT, is_published INTEGER DEFAULT 0, is_featured INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    await db.execute('CREATE TABLE lessons (id INTEGER PRIMARY KEY AUTOINCREMENT, course_id INTEGER NOT NULL, title TEXT NOT NULL, description TEXT, video_url TEXT, duration TEXT, order_index INTEGER DEFAULT 0, is_preview INTEGER DEFAULT 0, resources TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE)');
    await db.execute('CREATE TABLE enrollments (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, course_id INTEGER NOT NULL, progress REAL DEFAULT 0, completed_lessons TEXT DEFAULT "[]", is_completed INTEGER DEFAULT 0, enrolled_at TEXT DEFAULT CURRENT_TIMESTAMP, completed_at TEXT, FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN KEY (course_id) REFERENCES courses(id), UNIQUE(user_id, course_id))');
    await db.execute('CREATE TABLE cart (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, course_id INTEGER NOT NULL, added_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN KEY (course_id) REFERENCES courses(id), UNIQUE(user_id, course_id))');
    await db.execute('CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT)');
    await _insertSampleData(db);
  }

  Future<void> _insertSampleData(Database db) async {
    await db.insert('users', {'name': 'أحمد', 'email': 'admin@example.com', 'password': 'password123', 'role': 'admin'});
    await db.insert('courses', {'title': 'أساسيات البرمجة بلغة Python', 'description': 'تعلم أساسيات البرمجة من الصفر', 'instructor_name': 'محمد علي', 'price': 99.99, 'discount_price': 49.99, 'rating': 4.8, 'students_count': 1250, 'duration': '12 ساعة', 'category': 'برمجة', 'level': 'beginner', 'is_published': 1, 'is_featured': 1});
    await db.insert('courses', {'title': 'تطوير تطبيقات Flutter', 'description': 'تعلم بناء تطبيقات الموبايل', 'instructor_name': 'محمد علي', 'price': 149.99, 'discount_price': 79.99, 'rating': 4.9, 'students_count': 890, 'duration': '20 ساعة', 'category': 'تطوير موبايل', 'level': 'intermediate', 'is_published': 1, 'is_featured': 1});
  }

  Future<User?> getUserByEmail(String email) async {
    final db = await database;
    final maps = await db.query('users', where: 'email = ?', whereArgs: [email]);
    if (maps.isEmpty) return null;
    return User.fromJson(maps.first);
  }

  Future<User?> getUserByFirebaseUid(String uid) async {
    final db = await database;
    final maps = await db.query('users', where: 'firebase_uid = ?', whereArgs: [uid]);
    if (maps.isEmpty) return null;
    return User.fromJson(maps.first);
  }

  Future<int> createUser(User user) async {
    final db = await database;
    return await db.insert('users', user.toJson()..remove('id'));
  }

  Future<int> updateUser(User user) async {
    final db = await database;
    return await db.update('users', user.toJson(), where: 'id = ?', whereArgs: [user.id]);
  }

  Future<User?> login(String email, String password) async {
    final db = await database;
    final maps = await db.query('users', where: 'email = ? AND password = ? AND is_active = 1', whereArgs: [email, password]);
    if (maps.isEmpty) return null;
    return User.fromJson(maps.first);
  }

  Future<List<Course>> getAllCourses({String? category, String? level, String? search, String? sortBy, bool featuredOnly = false}) async {
    final db = await database;
    String whereClause = 'is_published = 1';
    List<dynamic> whereArgs = [];
    if (category != null) { whereClause += ' AND category = ?'; whereArgs.add(category); }
    if (featuredOnly) { whereClause += ' AND is_featured = 1'; }
    final maps = await db.query('courses', where: whereClause, whereArgs: whereArgs);
    return maps.map((m) => Course.fromJson(m)).toList();
  }

  Future<Course?> getCourseById(int id) async {
    final db = await database;
    final maps = await db.query('courses', where: 'id = ?', whereArgs: [id]);
    if (maps.isEmpty) return null;
    final lessons = await getLessonsByCourseId(id);
    return Course.fromJson({...maps.first, 'lessons': lessons.map((l) => l.toJson()).toList()});
  }

  Future<List<Lesson>> getLessonsByCourseId(int courseId) async {
    final db = await database;
    final maps = await db.query('lessons', where: 'course_id = ?', whereArgs: [courseId], orderBy: 'order_index ASC');
    return maps.map((m) => Lesson.fromJson(m)).toList();
  }

  Future<List<String>> getCategories() async {
    final db = await database;
    final maps = await db.rawQuery('SELECT DISTINCT category FROM courses WHERE is_published = 1 AND category IS NOT NULL');
    return maps.map((m) => m['category'] as String).toList();
  }

  Future<int> enrollCourse(int userId, int courseId) async {
    final db = await database;
    await db.rawUpdate('UPDATE courses SET students_count = students_count + 1 WHERE id = ?', [courseId]);
    return await db.insert('enrollments', {'user_id': userId, 'course_id': courseId});
  }

  Future<List<Course>> getCartCourses(int userId) async {
    final db = await database;
    final maps = await db.rawQuery('SELECT c.* FROM courses c INNER JOIN cart ct ON c.id = ct.course_id WHERE ct.user_id = ?', [userId]);
    return maps.map((m) => Course.fromJson(m)).toList();
  }

  Future<int> addToCart(int userId, int courseId) async {
    final db = await database;
    return await db.insert('cart', {'user_id': userId, 'course_id': courseId});
  }

  Future<int> removeFromCart(int userId, int courseId) async {
    final db = await database;
    return await db.delete('cart', where: 'user_id = ? AND course_id = ?', whereArgs: [userId, courseId]);
  }

  Future<int> clearCart(int userId) async {
    final db = await database;
    return await db.delete('cart', where: 'user_id = ?', whereArgs: [userId]);
  }

  Future<bool> isInCart(int userId, int courseId) async {
    final db = await database;
    final maps = await db.query('cart', where: 'user_id = ? AND course_id = ?', whereArgs: [userId, courseId]);
    return maps.isNotEmpty;
  }

  Future<void> setSetting(String key, String value) async {
    final db = await database;
    await db.insert('settings', {'key': key, 'value': value}, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<String?> getSetting(String key) async {
    final db = await database;
    final maps = await db.query('settings', where: 'key = ?', whereArgs: [key]);
    if (maps.isEmpty) return null;
    return maps.first['value'] as String?;
  }
}
