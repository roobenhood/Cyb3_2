-- =============================================
-- Database Schema for E-Commerce Store / Courses Platform
-- قاعدة بيانات المتجر الإلكتروني / منصة الدورات
-- تم إصلاح وتوحيد الأسماء
-- =============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS ecommerce_store
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ecommerce_store;

-- =============================================
-- Users Table - جدول المستخدمين
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(255) DEFAULT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'user', 'instructor') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active),
    INDEX idx_firebase_uid (firebase_uid)
) ENGINE=InnoDB;

-- =============================================
-- Categories Table - جدول التصنيفات
-- =============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'category',
    image VARCHAR(255) DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_name (name),
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- Products Table - جدول المنتجات
-- يستخدم أيضاً للدورات التعليمية
-- =============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    discount_price DECIMAL(10, 2) DEFAULT NULL,
    discount_start TIMESTAMP NULL DEFAULT NULL,
    discount_end TIMESTAMP NULL DEFAULT NULL,
    sku VARCHAR(100) DEFAULT NULL UNIQUE,
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    thumbnail VARCHAR(255) DEFAULT NULL,
    images JSON DEFAULT NULL,
    
    -- للدورات التعليمية
    instructor_id INT DEFAULT NULL,
    instructor_name VARCHAR(100) DEFAULT NULL,
    duration VARCHAR(50) DEFAULT NULL,
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    language VARCHAR(20) DEFAULT 'ar',
    
    -- إحصائيات
    view_count INT DEFAULT 0,
    rating DECIMAL(3, 2) DEFAULT 0,
    rating_count INT DEFAULT 0,
    sales_count INT DEFAULT 0,
    
    -- حالات
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    is_digital TINYINT(1) DEFAULT 0,
    
    -- SEO
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_category (category_id),
    INDEX idx_price (price),
    INDEX idx_featured (is_featured),
    INDEX idx_active (is_active),
    INDEX idx_instructor (instructor_id),
    INDEX idx_slug (slug),
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- Addresses Table - جدول العناوين
-- =============================================
CREATE TABLE IF NOT EXISTS addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    country VARCHAR(100) DEFAULT 'السعودية',
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100) DEFAULT NULL,
    street VARCHAR(255) NOT NULL,
    building VARCHAR(50) DEFAULT NULL,
    floor VARCHAR(20) DEFAULT NULL,
    apartment VARCHAR(20) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_default (is_default),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Cart Table - جدول السلة
-- =============================================
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_cart_item (user_id, product_id),
    INDEX idx_user (user_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Orders Table - جدول الطلبات
-- =============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    address_id INT DEFAULT NULL,
    
    -- المبالغ
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0,
    tax DECIMAL(10, 2) NOT NULL DEFAULT 0,
    shipping DECIMAL(10, 2) NOT NULL DEFAULT 0,
    discount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    
    -- الحالة
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT 'cash_on_delivery',
    
    -- معلومات إضافية
    notes TEXT DEFAULT NULL,
    admin_notes TEXT DEFAULT NULL,
    shipped_at TIMESTAMP NULL DEFAULT NULL,
    delivered_at TIMESTAMP NULL DEFAULT NULL,
    cancelled_at TIMESTAMP NULL DEFAULT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_order_number (order_number),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (address_id) REFERENCES addresses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- Order Items Table - جدول عناصر الطلبات
-- =============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_thumbnail VARCHAR(255) DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_order (order_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================
-- Favorites Table - جدول المفضلة
-- =============================================
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_favorite (user_id, product_id),
    INDEX idx_user (user_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Reviews Table - جدول التقييمات
-- =============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT DEFAULT NULL,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_review (user_id, product_id),
    INDEX idx_user (user_id),
    INDEX idx_product (product_id),
    INDEX idx_rating (rating),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Lessons Table - جدول الدروس (للدورات التعليمية)
-- =============================================
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    duration INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_free TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_course (course_id),
    INDEX idx_order (sort_order),
    FOREIGN KEY (course_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Enrollments Table - جدول التسجيل في الدورات
-- =============================================
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress INT DEFAULT 0,
    completed_lessons JSON DEFAULT NULL,
    last_lesson_id INT DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_enrollment (user_id, course_id),
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================
-- Settings Table - جدول الإعدادات
-- =============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    `value` TEXT DEFAULT NULL,
    `type` VARCHAR(20) DEFAULT 'string',
    `group` VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_key (`key`),
    INDEX idx_group (`group`)
) ENGINE=InnoDB;

-- =============================================
-- Insert Default Data - بيانات افتراضية
-- =============================================

-- إضافة تصنيفات افتراضية
INSERT INTO categories (name, name_en, icon, sort_order) VALUES
('البرمجة والتطوير', 'Programming', 'code', 1),
('التصميم والجرافيك', 'Design', 'palette', 2),
('التسويق الرقمي', 'Marketing', 'trending_up', 3),
('الأعمال والإدارة', 'Business', 'business', 4),
('اللغات', 'Languages', 'translate', 5),
('التصوير والفيديو', 'Photography', 'camera_alt', 6)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- إضافة إعدادات افتراضية
INSERT INTO settings (`key`, `value`, `type`, `group`) VALUES
('site_name', 'المتجر الإلكتروني', 'string', 'general'),
('site_description', 'أفضل منصة للتسوق والتعلم عبر الإنترنت', 'string', 'general'),
('currency', 'SAR', 'string', 'payment'),
('tax_rate', '0.15', 'float', 'payment'),
('shipping_cost', '25', 'float', 'shipping'),
('free_shipping_threshold', '500', 'float', 'shipping'),
('contact_email', 'info@example.com', 'string', 'contact'),
('contact_phone', '+966 12 345 6789', 'string', 'contact')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- إضافة مستخدم أدمن افتراضي (كلمة المرور: admin123)
INSERT INTO users (name, email, password, role, is_active) VALUES
('المدير', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
