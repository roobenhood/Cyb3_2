-- =============================================
-- Database Schema for E-Commerce Store
-- قاعدة بيانات المتجر الإلكتروني
-- =============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS ecommerce_store
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ecommerce_store;

-- =============================================
-- Users Table - جدول المستخدمين
-- =============================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'vendor', 'customer') DEFAULT 'customer',
    is_active TINYINT(1) DEFAULT 1,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- =============================================
-- Addresses Table - جدول العناوين
-- =============================================
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'السعودية',
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    street VARCHAR(255) NOT NULL,
    building_number VARCHAR(50),
    postal_code VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Categories Table - جدول التصنيفات
-- =============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    description TEXT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =============================================
-- Products Table - جدول المنتجات
-- =============================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    name_en VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    description TEXT NOT NULL,
    short_description VARCHAR(500),
    category_id INT NOT NULL,
    vendor_id INT DEFAULT NULL,
    sku VARCHAR(100) UNIQUE,
    barcode VARCHAR(100),
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    cost_price DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    min_stock INT DEFAULT 5,
    weight DECIMAL(10,2) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT 'piece',
    image VARCHAR(255) DEFAULT NULL,
    images TEXT DEFAULT NULL COMMENT 'JSON array of image URLs',
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    views INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_category (category_id),
    INDEX idx_vendor (vendor_id),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active),
    INDEX idx_price (price),
    INDEX idx_stock (stock),
    FULLTEXT INDEX idx_search (name, description)
) ENGINE=InnoDB;

-- =============================================
-- Product Variants Table - جدول متغيرات المنتجات
-- =============================================
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    sku VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    attributes TEXT COMMENT 'JSON object of attributes',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- =============================================
-- Cart Table - جدول السلة
-- =============================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,

    UNIQUE KEY unique_cart_item (user_id, product_id, variant_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Favorites Table - جدول المفضلة
-- =============================================
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY unique_favorite (user_id, product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Orders Table - جدول الطلبات
-- =============================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'wallet', 'bank_transfer') DEFAULT 'cash',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    shipping_cost DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_code VARCHAR(50) DEFAULT NULL,
    shipping_address_id INT,
    shipping_name VARCHAR(100),
    shipping_phone VARCHAR(20),
    shipping_city VARCHAR(100),
    shipping_address TEXT,
    notes TEXT,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shipping_address_id) REFERENCES addresses(id) ON DELETE SET NULL,

    INDEX idx_user (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =============================================
-- Order Items Table - جدول عناصر الطلبات
-- =============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    variant_name VARCHAR(100),
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,

    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- =============================================
-- Reviews Table - جدول التقييمات
-- =============================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT DEFAULT NULL,
    images TEXT COMMENT 'JSON array of image URLs',
    is_approved TINYINT(1) DEFAULT 0,
    is_verified_purchase TINYINT(1) DEFAULT 0,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,

    UNIQUE KEY unique_review (user_id, product_id),
    INDEX idx_product (product_id),
    INDEX idx_rating (rating),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB;

-- =============================================
-- Coupons Table - جدول الكوبونات
-- =============================================
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    type ENUM('fixed', 'percentage') DEFAULT 'fixed',
    value DECIMAL(10,2) NOT NULL,
    min_order_value DECIMAL(10,2) DEFAULT 0.00,
    max_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    usage_count INT DEFAULT 0,
    user_limit INT DEFAULT 1,
    start_date TIMESTAMP NULL,
    end_date TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =============================================
-- Settings Table - جدول الإعدادات
-- =============================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_type VARCHAR(50) DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- =============================================
-- Password Resets Table - جدول إعادة تعيين كلمة المرور
-- =============================================
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,

    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- =============================================
-- Notifications Table - جدول الإشعارات
-- =============================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT,
    data TEXT COMMENT 'JSON data',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB;

-- =============================================
-- Sample Data - بيانات تجريبية
-- =============================================

-- Insert Admin User (password: password)
INSERT INTO users (name, email, password, role, is_active, email_verified_at) VALUES
('المدير', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());

-- Insert Vendor (password: password)
INSERT INTO users (name, email, password, role, is_active, email_verified_at) VALUES
('متجر الإلكترونيات', 'vendor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor', 1, NOW());

-- Insert Customer (password: password)
INSERT INTO users (name, email, password, role, is_active, email_verified_at) VALUES
('أحمد العميل', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1, NOW());

-- Insert Categories
INSERT INTO categories (name, name_en, description, icon, sort_order) VALUES
('الإلكترونيات', 'Electronics', 'أجهزة إلكترونية وملحقاتها', 'devices', 1),
('الملابس', 'Clothing', 'ملابس رجالية ونسائية وأطفال', 'checkroom', 2),
('الأثاث', 'Furniture', 'أثاث منزلي ومكتبي', 'chair', 3),
('الجوالات', 'Phones', 'هواتف ذكية وملحقاتها', 'smartphone', 4),
('الكتب', 'Books', 'كتب ومجلات', 'menu_book', 5),
('الرياضة', 'Sports', 'معدات رياضية', 'sports_soccer', 6),
('الجمال', 'Beauty', 'مستحضرات تجميل وعناية', 'spa', 7),
('المنزل', 'Home', 'مستلزمات منزلية', 'home', 8);

-- Insert Sample Products
INSERT INTO products (name, name_en, slug, description, short_description, category_id, vendor_id, sku, price, discount_price, stock, is_featured, rating, review_count) VALUES
('ايفون 15 برو ماكس', 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'هاتف ايفون 15 برو ماكس الجديد من ابل مع شريحة A17 Pro وكاميرا متطورة', 'أحدث هاتف من أبل', 4, 2, 'IPH15PM-256', 4999.00, 4499.00, 50, 1, 4.8, 120),
('سامسونج جالكسي S24 الترا', 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'هاتف سامسونج الرائد مع قلم S Pen وكاميرا 200 ميجابكسل', 'هاتف سامسونج الأقوى', 4, 2, 'SAM-S24U-256', 4299.00, NULL, 35, 1, 4.6, 85),
('لابتوب ماك بوك برو 16', 'MacBook Pro 16', 'macbook-pro-16', 'لابتوب ماك بوك برو 16 انش مع شريحة M3 Max وذاكرة 32 جيجا', 'أقوى لابتوب للمحترفين', 1, 2, 'MBP16-M3MAX', 12999.00, 11999.00, 15, 1, 4.9, 45),
('سماعات ايربودز برو 2', 'AirPods Pro 2', 'airpods-pro-2', 'سماعات ايربودز برو الجيل الثاني مع إلغاء الضوضاء النشط', 'سماعات لاسلكية متطورة', 1, 2, 'APP2-USB-C', 899.00, 799.00, 100, 1, 4.7, 200),
('تيشيرت قطن فاخر', 'Premium Cotton T-Shirt', 'premium-cotton-tshirt', 'تيشيرت قطن 100% مصري عالي الجودة متوفر بعدة ألوان', 'تيشيرت مريح وأنيق', 2, 2, 'TSH-CTN-M', 149.00, 99.00, 200, 0, 4.3, 50),
('كرسي مكتب مريح', 'Ergonomic Office Chair', 'ergonomic-office-chair', 'كرسي مكتب مريح مع دعم للظهر وارتفاع قابل للتعديل', 'كرسي مكتب احترافي', 3, 2, 'CHR-ERG-BLK', 799.00, NULL, 30, 0, 4.5, 35),
('ساعة ابل ووتش الترا 2', 'Apple Watch Ultra 2', 'apple-watch-ultra-2', 'ساعة ابل ووتش الترا 2 للمغامرين مع GPS ومقاومة للماء', 'ساعة ذكية للمغامرات', 1, 2, 'AWU2-49MM', 3199.00, 2999.00, 25, 1, 4.8, 60),
('كتاب تعلم البرمجة', 'Learn Programming Book', 'learn-programming-book', 'كتاب شامل لتعلم البرمجة من الصفر حتى الاحتراف', 'أفضل كتاب للمبتدئين', 5, 2, 'BK-PROG-AR', 89.00, 69.00, 500, 0, 4.6, 150);

-- Insert Sample Reviews
INSERT INTO reviews (user_id, product_id, rating, title, comment, is_approved, is_verified_purchase) VALUES
(3, 1, 5, 'أفضل هاتف استخدمته', 'جودة الكاميرا ممتازة والأداء سريع جداً، أنصح به بشدة', 1, 1),
(3, 3, 5, 'لابتوب احترافي', 'مثالي للعمل والتصميم، البطارية تدوم طويلاً', 1, 1),
(3, 4, 4, 'سماعات ممتازة', 'جودة الصوت رائعة وإلغاء الضوضاء فعال جداً', 1, 1);

-- Insert Sample Address
INSERT INTO addresses (user_id, name, phone, city, district, street, is_default) VALUES
(3, 'أحمد العميل', '0501234567', 'الرياض', 'حي العليا', 'شارع الملك فهد', 1);

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'المتجر الإلكتروني'),
('site_description', 'أفضل متجر إلكتروني للتسوق أونلاين'),
('contact_email', 'info@store.com'),
('contact_phone', '+966500000000'),
('currency', 'SAR'),
('currency_symbol', 'ر.س'),
('tax_rate', '15'),
('shipping_cost', '25'),
('free_shipping_threshold', '500'),
('min_order_value', '50');

-- Insert Sample Coupon
INSERT INTO coupons (code, description, type, value, min_order_value, max_discount, usage_limit, start_date, end_date) VALUES
('WELCOME10', 'خصم 10% للعملاء الجدد', 'percentage', 10.00, 100.00, 50.00, 1000, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('SAVE50', 'خصم 50 ريال', 'fixed', 50.00, 200.00, NULL, 500, NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH));
