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
    role ENUM('admin', 'customer') DEFAULT 'customer',
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
-- Categories Table - جدول الفئات
-- =============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_parent (parent_id)
) ENGINE=InnoDB;

-- =============================================
-- Products Table - جدول المنتجات
-- =============================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    images TEXT DEFAULT NULL COMMENT 'JSON array of image URLs',
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock INT DEFAULT 0,
    sku VARCHAR(50) DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,

    INDEX idx_category (category_id),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_active (is_active),
    INDEX idx_price (price),
    INDEX idx_stock (stock),
    FULLTEXT INDEX idx_search (name, description)
) ENGINE=InnoDB;

-- =============================================
-- Cart Table - جدول السلة
-- =============================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY unique_cart_item (user_id, product_id),
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
    user_id INT NOT NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping DECIMAL(10,2) DEFAULT 0.00,
    tax DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT DEFAULT NULL,
    billing_address TEXT DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_user (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB;

-- =============================================
-- Order Items Table - جدول عناصر الطلب
-- =============================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_image VARCHAR(255) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

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
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,

    UNIQUE KEY unique_review (user_id, product_id),
    INDEX idx_product (product_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB;

-- =============================================
-- Addresses Table - جدول العناوين
-- =============================================
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_user (user_id)
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
-- Settings Table - جدول الإعدادات
-- =============================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- =============================================
-- Sample Data - بيانات تجريبية
-- =============================================

-- Insert Admin User (password: admin123)
INSERT INTO users (name, email, password, role, is_active, email_verified_at) VALUES
('المدير', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());

-- Insert Customer (password: password)
INSERT INTO users (name, email, password, role, is_active, email_verified_at) VALUES
('أحمد محمد', 'customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1, NOW());

-- Insert Categories
INSERT INTO categories (name, description, icon, is_active) VALUES
('الإلكترونيات', 'أجهزة إلكترونية ومستلزماتها', 'devices', 1),
('الملابس', 'ملابس رجالية ونسائية وأطفال', 'checkroom', 1),
('الأحذية', 'أحذية رياضية وكلاسيكية', 'footprint', 1),
('الإكسسوارات', 'ساعات، نظارات، مجوهرات', 'watch', 1),
('المنزل والحديقة', 'أثاث ومستلزمات منزلية', 'home', 1);

-- Insert Sample Products
INSERT INTO products (name, description, category_id, price, discount_price, stock, is_featured, is_active, rating, review_count) VALUES
('هاتف ذكي سامسونج جالاكسي', 'هاتف ذكي بمواصفات عالية وكاميرا احترافية وشاشة AMOLED', 1, 2499.99, 1999.99, 50, 1, 1, 4.5, 128),
('لابتوب ماك بوك برو', 'لابتوب احترافي للمطورين والمصممين بمعالج M2', 1, 5999.99, NULL, 25, 1, 1, 4.8, 89),
('سماعات أيربودز برو', 'سماعات لاسلكية بخاصية إلغاء الضوضاء', 1, 899.99, 749.99, 100, 1, 1, 4.6, 256),
('ساعة آبل الذكية', 'ساعة ذكية لتتبع اللياقة والصحة', 1, 1599.99, 1399.99, 75, 0, 1, 4.4, 167),
('قميص رجالي كلاسيكي', 'قميص قطني بجودة عالية للمناسبات الرسمية', 2, 199.99, 149.99, 200, 0, 1, 4.2, 45),
('فستان نسائي أنيق', 'فستان سهرة أنيق بتصميم عصري', 2, 349.99, NULL, 80, 1, 1, 4.7, 78),
('حذاء رياضي نايك', 'حذاء رياضي مريح للجري والتمارين', 3, 449.99, 379.99, 150, 1, 1, 4.5, 234),
('حذاء رسمي جلد طبيعي', 'حذاء رجالي فاخر من الجلد الطبيعي', 3, 599.99, NULL, 60, 0, 1, 4.3, 56),
('نظارة شمسية ريبان', 'نظارة شمسية أصلية بحماية UV400', 4, 699.99, 549.99, 120, 0, 1, 4.6, 89),
('طاولة قهوة خشبية', 'طاولة قهوة أنيقة من الخشب الطبيعي', 5, 899.99, 749.99, 30, 0, 1, 4.4, 34);

-- Insert Sample Reviews
INSERT INTO reviews (user_id, product_id, rating, comment) VALUES
(2, 1, 5, 'منتج ممتاز والتوصيل سريع جداً'),
(2, 2, 5, 'أفضل لابتوب استخدمته في حياتي'),
(2, 3, 4, 'جودة صوت ممتازة لكن البطارية تحتاج تحسين');

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'المتجر الإلكتروني'),
('site_description', 'أفضل متجر إلكتروني عربي للتسوق أونلاين'),
('contact_email', 'info@example.com'),
('currency', 'SAR'),
('shipping_cost', '25.00'),
('free_shipping_threshold', '500.00'),
('tax_rate', '15');
