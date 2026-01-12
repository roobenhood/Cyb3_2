import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import '../models/product.dart';
import '../models/user.dart';
import '../models/category.dart';
import '../models/cart_item.dart';
import '../models/order.dart';

/// خدمة قاعدة البيانات المحلية
class DatabaseHelper {
  static final DatabaseHelper instance = DatabaseHelper._init();
  static Database? _database;

  DatabaseHelper._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('ecommerce_store.db');
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
        role TEXT DEFAULT 'customer',
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // جدول التصنيفات
    await db.execute('''
      CREATE TABLE categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        icon TEXT,
        image_url TEXT,
        parent_id INTEGER,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
      )
    ''');

    // جدول المنتجات
    await db.execute('''
      CREATE TABLE products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        category_id INTEGER,
        price REAL DEFAULT 0,
        discount_price REAL,
        image_url TEXT,
        images TEXT,
        stock INTEGER DEFAULT 0,
        rating REAL DEFAULT 0,
        review_count INTEGER DEFAULT 0,
        is_featured INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id)
      )
    ''');

    // جدول السلة
    await db.execute('''
      CREATE TABLE cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
      )
    ''');

    // جدول المفضلة
    await db.execute('''
      CREATE TABLE favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
      )
    ''');

    // جدول الطلبات
    await db.execute('''
      CREATE TABLE orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        order_number TEXT UNIQUE NOT NULL,
        subtotal REAL DEFAULT 0,
        shipping_cost REAL DEFAULT 0,
        discount REAL DEFAULT 0,
        total REAL DEFAULT 0,
        status TEXT DEFAULT 'pending',
        shipping_address TEXT,
        payment_method TEXT,
        notes TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
      )
    ''');

    // جدول عناصر الطلب
    await db.execute('''
      CREATE TABLE order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        product_name TEXT,
        product_price REAL,
        quantity INTEGER DEFAULT 1,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
      )
    ''');

    // جدول التقييمات
    await db.execute('''
      CREATE TABLE reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        rating INTEGER NOT NULL,
        comment TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
      )
    ''');

    // إدخال بيانات تجريبية
    await _insertSampleData(db);
  }

  Future<void> _upgradeDB(Database db, int oldVersion, int newVersion) async {
    // ترقية قاعدة البيانات
  }

  Future<void> _insertSampleData(Database db) async {
    // تصنيفات تجريبية
    await db.insert('categories', {'name': 'إلكترونيات', 'icon': 'devices', 'description': 'أجهزة إلكترونية ومحمولة'});
    await db.insert('categories', {'name': 'ملابس', 'icon': 'checkroom', 'description': 'ملابس رجالية ونسائية'});
    await db.insert('categories', {'name': 'أثاث منزلي', 'icon': 'weekend', 'description': 'أثاث وديكورات'});
    await db.insert('categories', {'name': 'رياضة', 'icon': 'sports_soccer', 'description': 'مستلزمات رياضية'});
    await db.insert('categories', {'name': 'كتب', 'icon': 'menu_book', 'description': 'كتب ومراجع'});

    // منتجات تجريبية
    await db.insert('products', {
      'name': 'هاتف ذكي Samsung Galaxy',
      'description': 'هاتف ذكي بمواصفات عالية',
      'category_id': 1,
      'price': 2999.99,
      'discount_price': 2499.99,
      'stock': 50,
      'is_featured': 1,
    });
    await db.insert('products', {
      'name': 'لابتوب Dell XPS',
      'description': 'لابتوب احترافي للأعمال',
      'category_id': 1,
      'price': 5999.99,
      'stock': 20,
      'is_featured': 1,
    });
    await db.insert('products', {
      'name': 'قميص قطني',
      'description': 'قميص قطني مريح',
      'category_id': 2,
      'price': 149.99,
      'discount_price': 99.99,
      'stock': 100,
    });
  }

  // ==================== المستخدمين ====================

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

  Future<User?> getUserByFirebaseUid(String uid) async {
    final db = await database;
    final maps = await db.query(
      'users',
      where: 'firebase_uid = ?',
      whereArgs: [uid],
    );
    if (maps.isEmpty) return null;
    return User.fromJson(maps.first);
  }

  Future<int> insertUser(User user) async {
    final db = await database;
    return await db.insert('users', user.toMap());
  }

  Future<int> updateUser(User user) async {
    final db = await database;
    return await db.update(
      'users',
      user.toMap(),
      where: 'id = ?',
      whereArgs: [user.id],
    );
  }

  // ==================== المنتجات ====================

  Future<List<Product>> getProducts({int? categoryId, String? search, int limit = 50, int offset = 0}) async {
    final db = await database;
    String where = 'is_active = 1';
    List<dynamic> whereArgs = [];

    if (categoryId != null) {
      where += ' AND category_id = ?';
      whereArgs.add(categoryId);
    }

    if (search != null && search.isNotEmpty) {
      where += ' AND (name LIKE ? OR description LIKE ?)';
      whereArgs.add('%$search%');
      whereArgs.add('%$search%');
    }

    final maps = await db.query(
      'products',
      where: where,
      whereArgs: whereArgs,
      limit: limit,
      offset: offset,
      orderBy: 'created_at DESC',
    );

    return maps.map((e) => Product.fromJson(e)).toList();
  }

  Future<List<Product>> getFeaturedProducts() async {
    final db = await database;
    final maps = await db.query(
      'products',
      where: 'is_featured = 1 AND is_active = 1',
      limit: 10,
    );
    return maps.map((e) => Product.fromJson(e)).toList();
  }

  Future<Product?> getProductById(int id) async {
    final db = await database;
    final maps = await db.query(
      'products',
      where: 'id = ?',
      whereArgs: [id],
    );
    if (maps.isEmpty) return null;
    return Product.fromJson(maps.first);
  }

  // ==================== التصنيفات ====================

  Future<List<Category>> getCategories() async {
    final db = await database;
    final maps = await db.query('categories', where: 'is_active = 1');
    return maps.map((e) => Category.fromJson(e)).toList();
  }

  // ==================== السلة ====================

  Future<List<CartItem>> getCartItems(int userId) async {
    final db = await database;
    final maps = await db.rawQuery('''
      SELECT c.*, p.name as product_name, p.price as product_price, 
             p.discount_price as product_discount_price, p.image_url as product_image
      FROM cart c
      LEFT JOIN products p ON c.product_id = p.id
      WHERE c.user_id = ?
    ''', [userId]);

    return maps.map((e) {
      final product = Product(
        id: e['product_id'] as int,
        name: e['product_name'] as String? ?? '',
        description: '',
        price: (e['product_price'] as num?)?.toDouble() ?? 0,
        discountPrice: e['product_discount_price'] != null
            ? (e['product_discount_price'] as num).toDouble()
            : null,
        imageUrl: e['product_image'] as String? ?? '',
        categoryId: 0,
      );
      return CartItem(
        id: e['id'] as int?,
        userId: e['user_id'] as int,
        productId: e['product_id'] as int,
        product: product,
        quantity: e['quantity'] as int? ?? 1,
      );
    }).toList();
  }

  Future<void> addToCart(int userId, int productId, {int quantity = 1}) async {
    final db = await database;
    await db.insert(
      'cart',
      {'user_id': userId, 'product_id': productId, 'quantity': quantity},
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<void> updateCartQuantity(int userId, int productId, int quantity) async {
    final db = await database;
    if (quantity <= 0) {
      await removeFromCart(userId, productId);
    } else {
      await db.update(
        'cart',
        {'quantity': quantity},
        where: 'user_id = ? AND product_id = ?',
        whereArgs: [userId, productId],
      );
    }
  }

  Future<void> removeFromCart(int userId, int productId) async {
    final db = await database;
    await db.delete(
      'cart',
      where: 'user_id = ? AND product_id = ?',
      whereArgs: [userId, productId],
    );
  }

  Future<void> clearCart(int userId) async {
    final db = await database;
    await db.delete('cart', where: 'user_id = ?', whereArgs: [userId]);
  }

  // ==================== المفضلة ====================

  Future<List<Product>> getFavorites(int userId) async {
    final db = await database;
    final maps = await db.rawQuery('''
      SELECT p.* FROM favorites f
      LEFT JOIN products p ON f.product_id = p.id
      WHERE f.user_id = ?
    ''', [userId]);
    return maps.map((e) => Product.fromJson(e)).toList();
  }

  Future<bool> isFavorite(int userId, int productId) async {
    final db = await database;
    final maps = await db.query(
      'favorites',
      where: 'user_id = ? AND product_id = ?',
      whereArgs: [userId, productId],
    );
    return maps.isNotEmpty;
  }

  Future<void> addToFavorites(int userId, int productId) async {
    final db = await database;
    await db.insert(
      'favorites',
      {'user_id': userId, 'product_id': productId},
      conflictAlgorithm: ConflictAlgorithm.ignore,
    );
  }

  Future<void> removeFromFavorites(int userId, int productId) async {
    final db = await database;
    await db.delete(
      'favorites',
      where: 'user_id = ? AND product_id = ?',
      whereArgs: [userId, productId],
    );
  }

  // إغلاق قاعدة البيانات
  Future<void> close() async {
    final db = await database;
    await db.close();
    _database = null;
  }
}
