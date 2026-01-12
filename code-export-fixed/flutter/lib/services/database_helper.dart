import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import '../models/course.dart';
import '../models/user.dart';
import '../models/enrollment.dart';

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

    return await openDatabase(
      path,
      version: 1,
      onCreate: _createDB,
      onUpgrade: _upgradeDB,
    );
  }

  Future<void> _createDB(Database db, int version) async {
    // جدول المستخدمين
    await db.execute('''
      CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        firebase_uid TEXT UNIQUE,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        phone TEXT,
        avatar_url TEXT,
        role TEXT DEFAULT 'student',
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // جدول الكورسات
    await db.execute('''
      CREATE TABLE courses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        instructor_id INTEGER,
        instructor_name TEXT,
        price REAL DEFAULT 0,
        discount_price REAL,
        rating REAL DEFAULT 0,
        students_count INTEGER DEFAULT 0,
        duration TEXT,
        category TEXT,
        level TEXT DEFAULT 'beginner',
        language TEXT DEFAULT 'ar',
        image_url TEXT,
        preview_video_url TEXT,
        is_published INTEGER DEFAULT 0,
        is_featured INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (instructor_id) REFERENCES users(id)
      )
    ''');

    // جدول الدروس
    await db.execute('''
      CREATE TABLE lessons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        video_url TEXT,
        duration TEXT,
        order_index INTEGER DEFAULT 0,
        is_preview INTEGER DEFAULT 0,
        resources TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
      )
    ''');

    // جدول التسجيل في الكورسات
    await db.execute('''
      CREATE TABLE enrollments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        progress REAL DEFAULT 0,
        completed_lessons TEXT DEFAULT '[]',
        is_completed INTEGER DEFAULT 0,
        enrolled_at TEXT DEFAULT CURRENT_TIMESTAMP,
        completed_at TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id),
        UNIQUE(user_id, course_id)
      )
    ''');

    // جدول السلة
    await db.execute('''
      CREATE TABLE cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        added_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id),
        UNIQUE(user_id, course_id)
      )
    ''');

    // جدول المفضلة
    await db.execute('''
      CREATE TABLE favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        added_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id),
        UNIQUE(user_id, course_id)
      )
    ''');

    // جدول التقييمات
    await db.execute('''
      CREATE TABLE reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        course_id INTEGER NOT NULL,
        rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id),
        UNIQUE(user_id, course_id)
      )
    ''');

    // جدول الإشعارات
    await db.execute('''
      CREATE TABLE notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        message TEXT NOT NULL,
        type TEXT DEFAULT 'general',
        is_read INTEGER DEFAULT 0,
        data TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
      )
    ''');

    // جدول الإعدادات
    await db.execute('''
      CREATE TABLE settings (
        key TEXT PRIMARY KEY,
        value TEXT
      )
    ''');

    // إضافة بيانات تجريبية
    await _insertSampleData(db);
  }

  Future<void> _upgradeDB(Database db, int oldVersion, int newVersion) async {
    // التحديثات المستقبلية لقاعدة البيانات
  }

  Future<void> _insertSampleData(Database db) async {
    // إضافة مستخدم تجريبي
    await db.insert('users', {
      'name': 'أحمد محمد',
      'email': 'admin@example.com',
      'password': 'password123', // في الإنتاج: استخدم تشفير
      'role': 'admin',
    });

    await db.insert('users', {
      'name': 'محمد علي',
      'email': 'instructor@example.com',
      'password': 'password123',
      'role': 'instructor',
    });

    await db.insert('users', {
      'name': 'سارة أحمد',
      'email': 'student@example.com',
      'password': 'password123',
      'role': 'student',
    });

    // إضافة كورسات تجريبية
    final courses = [
      {
        'title': 'أساسيات البرمجة بلغة Python',
        'description': 'تعلم أساسيات البرمجة من الصفر باستخدام لغة Python الشهيرة. هذا الكورس مناسب للمبتدئين تماماً.',
        'instructor_id': 2,
        'instructor_name': 'محمد علي',
        'price': 99.99,
        'discount_price': 49.99,
        'rating': 4.8,
        'students_count': 1250,
        'duration': '12 ساعة',
        'category': 'برمجة',
        'level': 'beginner',
        'is_published': 1,
        'is_featured': 1,
      },
      {
        'title': 'تطوير تطبيقات Flutter',
        'description': 'تعلم بناء تطبيقات الموبايل لـ Android و iOS باستخدام Flutter و Dart.',
        'instructor_id': 2,
        'instructor_name': 'محمد علي',
        'price': 149.99,
        'discount_price': 79.99,
        'rating': 4.9,
        'students_count': 890,
        'duration': '20 ساعة',
        'category': 'تطوير موبايل',
        'level': 'intermediate',
        'is_published': 1,
        'is_featured': 1,
      },
      {
        'title': 'تصميم واجهات المستخدم UI/UX',
        'description': 'تعلم أسس تصميم واجهات المستخدم وتجربة المستخدم باستخدام Figma.',
        'instructor_id': 2,
        'instructor_name': 'محمد علي',
        'price': 79.99,
        'rating': 4.7,
        'students_count': 650,
        'duration': '8 ساعات',
        'category': 'تصميم',
        'level': 'beginner',
        'is_published': 1,
      },
      {
        'title': 'تطوير الويب الكامل Full Stack',
        'description': 'تعلم تطوير مواقع الويب من الصفر حتى الاحتراف - HTML, CSS, JavaScript, React, Node.js',
        'instructor_id': 2,
        'instructor_name': 'محمد علي',
        'price': 199.99,
        'discount_price': 99.99,
        'rating': 4.6,
        'students_count': 2100,
        'duration': '35 ساعة',
        'category': 'تطوير ويب',
        'level': 'beginner',
        'is_published': 1,
        'is_featured': 1,
      },
    ];

    for (var course in courses) {
      await db.insert('courses', course);
    }

    // إضافة دروس تجريبية
    final lessons = [
      {'course_id': 1, 'title': 'مقدمة في Python', 'duration': '15:00', 'order_index': 1, 'is_preview': 1},
      {'course_id': 1, 'title': 'تثبيت بيئة التطوير', 'duration': '20:00', 'order_index': 2, 'is_preview': 0},
      {'course_id': 1, 'title': 'المتغيرات وأنواع البيانات', 'duration': '30:00', 'order_index': 3, 'is_preview': 0},
      {'course_id': 1, 'title': 'العمليات الحسابية', 'duration': '25:00', 'order_index': 4, 'is_preview': 0},
      {'course_id': 2, 'title': 'مقدمة في Flutter', 'duration': '20:00', 'order_index': 1, 'is_preview': 1},
      {'course_id': 2, 'title': 'تثبيت Flutter و Dart', 'duration': '30:00', 'order_index': 2, 'is_preview': 0},
      {'course_id': 2, 'title': 'أول تطبيق Flutter', 'duration': '45:00', 'order_index': 3, 'is_preview': 0},
    ];

    for (var lesson in lessons) {
      await db.insert('lessons', lesson);
    }
  }

  // ===================== عمليات المستخدمين =====================

  Future<User?> getUserByEmail(String email) async {
    final db = await database;
    final maps = await db.query(
      'users',
      where: 'email = ?',
      whereArgs: [email],
    );
    if (maps.isEmpty) return null;
    return User.fromJson(maps.first);
  }

  Future<User?> getUserById(int id) async {
    final db = await database;
    final maps = await db.query(
      'users',
      where: 'id = ?',
      whereArgs: [id],
    );
    if (maps.isEmpty) return null;
    return User.fromJson(maps.first);
  }

  Future<int> createUser(User user) async {
    final db = await database;
    return await db.insert('users', user.toJson()..remove('id'));
  }

  Future<int> updateUser(User user) async {
    final db = await database;
    return await db.update(
      'users',
      user.toJson()..['updated_at'] = DateTime.now().toIso8601String(),
      where: 'id = ?',
      whereArgs: [user.id],
    );
  }

  Future<User?> login(String email, String password) async {
    final db = await database;
    final maps = await db.query(
      'users',
      where: 'email = ? AND password = ? AND is_active = 1',
      whereArgs: [email, password],
    );
    if (maps.isEmpty) return null;
    return User.fromJson(maps.first);
  }

  // ===================== عمليات الكورسات =====================

  Future<List<Course>> getAllCourses({
    String? category,
    String? level,
    String? search,
    String? sortBy,
    bool featuredOnly = false,
  }) async {
    final db = await database;
    
    String whereClause = 'is_published = 1';
    List<dynamic> whereArgs = [];

    if (category != null && category.isNotEmpty) {
      whereClause += ' AND category = ?';
      whereArgs.add(category);
    }

    if (level != null && level.isNotEmpty) {
      whereClause += ' AND level = ?';
      whereArgs.add(level);
    }

    if (search != null && search.isNotEmpty) {
      whereClause += ' AND (title LIKE ? OR description LIKE ?)';
      whereArgs.add('%$search%');
      whereArgs.add('%$search%');
    }

    if (featuredOnly) {
      whereClause += ' AND is_featured = 1';
    }

    String? orderBy;
    if (sortBy != null) {
      switch (sortBy) {
        case 'newest':
          orderBy = 'created_at DESC';
          break;
        case 'popular':
          orderBy = 'students_count DESC';
          break;
        case 'rating':
          orderBy = 'rating DESC';
          break;
        case 'price_low':
          orderBy = 'COALESCE(discount_price, price) ASC';
          break;
        case 'price_high':
          orderBy = 'COALESCE(discount_price, price) DESC';
          break;
      }
    }

    final maps = await db.query(
      'courses',
      where: whereClause,
      whereArgs: whereArgs,
      orderBy: orderBy,
    );

    List<Course> courses = [];
    for (var map in maps) {
      final lessons = await getLessonsByCourseId(map['id'] as int);
      courses.add(Course.fromJson({...map, 'lessons': lessons.map((l) => l.toJson()).toList()}));
    }
    return courses;
  }

  Future<Course?> getCourseById(int id) async {
    final db = await database;
    final maps = await db.query(
      'courses',
      where: 'id = ?',
      whereArgs: [id],
    );
    if (maps.isEmpty) return null;
    
    final lessons = await getLessonsByCourseId(id);
    return Course.fromJson({...maps.first, 'lessons': lessons.map((l) => l.toJson()).toList()});
  }

  Future<int> createCourse(Course course) async {
    final db = await database;
    return await db.insert('courses', course.toJson()..remove('id')..remove('lessons'));
  }

  Future<int> updateCourse(Course course) async {
    final db = await database;
    return await db.update(
      'courses',
      course.toJson()..remove('lessons')..['updated_at'] = DateTime.now().toIso8601String(),
      where: 'id = ?',
      whereArgs: [course.id],
    );
  }

  Future<int> deleteCourse(int id) async {
    final db = await database;
    return await db.delete(
      'courses',
      where: 'id = ?',
      whereArgs: [id],
    );
  }

  Future<List<String>> getCategories() async {
    final db = await database;
    final maps = await db.rawQuery(
      'SELECT DISTINCT category FROM courses WHERE is_published = 1 AND category IS NOT NULL',
    );
    return maps.map((m) => m['category'] as String).toList();
  }

  // ===================== عمليات الدروس =====================

  Future<List<Lesson>> getLessonsByCourseId(int courseId) async {
    final db = await database;
    final maps = await db.query(
      'lessons',
      where: 'course_id = ?',
      whereArgs: [courseId],
      orderBy: 'order_index ASC',
    );
    return maps.map((m) => Lesson.fromJson(m)).toList();
  }

  Future<int> createLesson(Lesson lesson) async {
    final db = await database;
    return await db.insert('lessons', lesson.toJson()..remove('id'));
  }

  // ===================== عمليات التسجيل =====================

  Future<bool> isEnrolled(int userId, int courseId) async {
    final db = await database;
    final maps = await db.query(
      'enrollments',
      where: 'user_id = ? AND course_id = ?',
      whereArgs: [userId, courseId],
    );
    return maps.isNotEmpty;
  }

  Future<int> enrollCourse(int userId, int courseId) async {
    final db = await database;
    
    // تحديث عدد الطلاب
    await db.rawUpdate(
      'UPDATE courses SET students_count = students_count + 1 WHERE id = ?',
      [courseId],
    );
    
    return await db.insert('enrollments', {
      'user_id': userId,
      'course_id': courseId,
    });
  }

  Future<int> unenrollCourse(int userId, int courseId) async {
    final db = await database;
    
    // تحديث عدد الطلاب
    await db.rawUpdate(
      'UPDATE courses SET students_count = students_count - 1 WHERE id = ? AND students_count > 0',
      [courseId],
    );
    
    return await db.delete(
      'enrollments',
      where: 'user_id = ? AND course_id = ?',
      whereArgs: [userId, courseId],
    );
  }

  Future<List<Course>> getEnrolledCourses(int userId) async {
    final db = await database;
    final maps = await db.rawQuery('''
      SELECT c.*, e.progress, e.is_completed
      FROM courses c
      INNER JOIN enrollments e ON c.id = e.course_id
      WHERE e.user_id = ?
      ORDER BY e.enrolled_at DESC
    ''', [userId]);
    
    List<Course> courses = [];
    for (var map in maps) {
      final lessons = await getLessonsByCourseId(map['id'] as int);
      courses.add(Course.fromJson({...map, 'lessons': lessons.map((l) => l.toJson()).toList()}));
    }
    return courses;
  }

  Future<int> updateProgress(int userId, int courseId, double progress, List<int> completedLessons) async {
    final db = await database;
    return await db.update(
      'enrollments',
      {
        'progress': progress,
        'completed_lessons': completedLessons.join(','),
        'is_completed': progress >= 100 ? 1 : 0,
        'completed_at': progress >= 100 ? DateTime.now().toIso8601String() : null,
      },
      where: 'user_id = ? AND course_id = ?',
      whereArgs: [userId, courseId],
    );
  }

  // ===================== عمليات السلة =====================

  Future<List<Course>> getCartItems(int userId) async {
    final db = await database;
    final maps = await db.rawQuery('''
      SELECT c.*
      FROM courses c
      INNER JOIN cart ct ON c.id = ct.course_id
      WHERE ct.user_id = ?
      ORDER BY ct.added_at DESC
    ''', [userId]);
    
    List<Course> courses = [];
    for (var map in maps) {
      final lessons = await getLessonsByCourseId(map['id'] as int);
      courses.add(Course.fromJson({...map, 'lessons': lessons.map((l) => l.toJson()).toList()}));
    }
    return courses;
  }

  Future<int> addToCart(int userId, int courseId) async {
    final db = await database;
    return await db.insert('cart', {
      'user_id': userId,
      'course_id': courseId,
    });
  }

  Future<int> removeFromCart(int userId, int courseId) async {
    final db = await database;
    return await db.delete(
      'cart',
      where: 'user_id = ? AND course_id = ?',
      whereArgs: [userId, courseId],
    );
  }

  Future<int> clearCart(int userId) async {
    final db = await database;
    return await db.delete(
      'cart',
      where: 'user_id = ?',
      whereArgs: [userId],
    );
  }

  Future<bool> isInCart(int userId, int courseId) async {
    final db = await database;
    final maps = await db.query(
      'cart',
      where: 'user_id = ? AND course_id = ?',
      whereArgs: [userId, courseId],
    );
    return maps.isNotEmpty;
  }

  // ===================== عمليات المفضلة =====================

  Future<List<Course>> getFavorites(int userId) async {
    final db = await database;
    final maps = await db.rawQuery('''
      SELECT c.*
      FROM courses c
      INNER JOIN favorites f ON c.id = f.course_id
      WHERE f.user_id = ?
      ORDER BY f.added_at DESC
    ''', [userId]);
    
    return maps.map((m) => Course.fromJson(m)).toList();
  }

  Future<int> addToFavorites(int userId, int courseId) async {
    final db = await database;
    return await db.insert('favorites', {
      'user_id': userId,
      'course_id': courseId,
    });
  }

  Future<int> removeFromFavorites(int userId, int courseId) async {
    final db = await database;
    return await db.delete(
      'favorites',
      where: 'user_id = ? AND course_id = ?',
      whereArgs: [userId, courseId],
    );
  }

  Future<bool> isFavorite(int userId, int courseId) async {
    final db = await database;
    final maps = await db.query(
      'favorites',
      where: 'user_id = ? AND course_id = ?',
      whereArgs: [userId, courseId],
    );
    return maps.isNotEmpty;
  }

  // ===================== التقييمات =====================

  Future<int> addReview(int userId, int courseId, int rating, String? comment) async {
    final db = await database;
    
    // إضافة التقييم
    final id = await db.insert('reviews', {
      'user_id': userId,
      'course_id': courseId,
      'rating': rating,
      'comment': comment,
    });
    
    // تحديث متوسط التقييم
    await _updateCourseRating(courseId);
    
    return id;
  }

  Future<void> _updateCourseRating(int courseId) async {
    final db = await database;
    final result = await db.rawQuery(
      'SELECT AVG(rating) as avg_rating FROM reviews WHERE course_id = ?',
      [courseId],
    );
    
    if (result.isNotEmpty && result.first['avg_rating'] != null) {
      await db.update(
        'courses',
        {'rating': result.first['avg_rating']},
        where: 'id = ?',
        whereArgs: [courseId],
      );
    }
  }

  // ===================== إغلاق قاعدة البيانات =====================

  Future<void> close() async {
    final db = await database;
    db.close();
  }
}
