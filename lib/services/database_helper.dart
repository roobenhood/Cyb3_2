import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import '../models/product.dart';
import '../models/cart_item.dart';

class DatabaseHelper {
  static final DatabaseHelper _instance = DatabaseHelper._internal();
  factory DatabaseHelper() => _instance;
  DatabaseHelper._internal();

  static Database? _database;

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDatabase();
    return _database!;
  }

  Future<Database> _initDatabase() async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, 'ecommerce.db');

    return await openDatabase(
      path,
      version: 1,
      onCreate: _onCreate,
    );
  }

  Future<void> _onCreate(Database db, int version) async {
    // جدول المفضلة
    await db.execute('''
      CREATE TABLE favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER UNIQUE,
        product_data TEXT,
        created_at TEXT
      )
    ''');

    // جدول السلة المحلية
    await db.execute('''
      CREATE TABLE cart (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER UNIQUE,
        product_data TEXT,
        quantity INTEGER,
        price REAL,
        created_at TEXT
      )
    ''');

    // جدول المنتجات المخزنة مؤقتاً
    await db.execute('''
      CREATE TABLE cached_products (
        id INTEGER PRIMARY KEY,
        data TEXT,
        cached_at TEXT
      )
    ''');
  }

  // ==================== المفضلة ====================
  
  Future<List<Product>> getFavorites() async {
    final db = await database;
    final results = await db.query('favorites', orderBy: 'created_at DESC');
    
    return results.map((row) {
      final productData = row['product_data'] as String;
      return Product.fromJson(Map<String, dynamic>.from(
        Uri.decodeFull(productData) as Map
      ));
    }).toList();
  }

  Future<bool> isFavorite(int productId) async {
    final db = await database;
    final results = await db.query(
      'favorites',
      where: 'product_id = ?',
      whereArgs: [productId],
    );
    return results.isNotEmpty;
  }

  Future<void> addToFavorites(Product product) async {
    final db = await database;
    await db.insert(
      'favorites',
      {
        'product_id': product.id,
        'product_data': Uri.encodeFull(product.toJson().toString()),
        'created_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }

  Future<void> removeFromFavorites(int productId) async {
    final db = await database;
    await db.delete(
      'favorites',
      where: 'product_id = ?',
      whereArgs: [productId],
    );
  }

  // ==================== السلة ====================
  
  Future<List<CartItem>> getCartItems() async {
    final db = await database;
    final results = await db.query('cart', orderBy: 'created_at DESC');
    
    return results.map((row) => CartItem(
      id: row['id'] as int,
      productId: row['product_id'] as int,
      quantity: row['quantity'] as int,
      price: row['price'] as double,
      createdAt: DateTime.parse(row['created_at'] as String),
    )).toList();
  }

  Future<void> addToCart(int productId, double price, {int quantity = 1}) async {
    final db = await database;
    
    final existing = await db.query(
      'cart',
      where: 'product_id = ?',
      whereArgs: [productId],
    );

    if (existing.isNotEmpty) {
      final currentQty = existing.first['quantity'] as int;
      await db.update(
        'cart',
        {'quantity': currentQty + quantity},
        where: 'product_id = ?',
        whereArgs: [productId],
      );
    } else {
      await db.insert('cart', {
        'product_id': productId,
        'quantity': quantity,
        'price': price,
        'created_at': DateTime.now().toIso8601String(),
      });
    }
  }

  Future<void> updateCartQuantity(int productId, int quantity) async {
    final db = await database;
    if (quantity <= 0) {
      await removeFromCart(productId);
    } else {
      await db.update(
        'cart',
        {'quantity': quantity},
        where: 'product_id = ?',
        whereArgs: [productId],
      );
    }
  }

  Future<void> removeFromCart(int productId) async {
    final db = await database;
    await db.delete(
      'cart',
      where: 'product_id = ?',
      whereArgs: [productId],
    );
  }

  Future<void> clearCart() async {
    final db = await database;
    await db.delete('cart');
  }

  Future<int> getCartCount() async {
    final db = await database;
    final result = await db.rawQuery('SELECT SUM(quantity) as count FROM cart');
    return (result.first['count'] as int?) ?? 0;
  }
}
