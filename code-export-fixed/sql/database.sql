-- =============================================
-- Database Schema for Course Platform
-- قاعدة بيانات منصة الدورات التعليمية
-- =============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS courses_platform 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE courses_platform;

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
    role ENUM('admin', 'instructor', 'student') DEFAULT 'student',
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
-- Categories Table - جدول التصنيفات
-- =============================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
) ENGINE=InnoDB;

-- =============================================
-- Courses Table - جدول الدورات
-- =============================================
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    instructor_id INT NOT NULL,
    category_id INT NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    duration INT DEFAULT 0 COMMENT 'Duration in minutes',
    is_featured TINYINT(1) DEFAULT 0,
    is_published TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    
    INDEX idx_instructor (instructor_id),
    INDEX idx_category (category_id),
    INDEX idx_level (level),
    INDEX idx_is_featured (is_featured),
    INDEX idx_is_published (is_published),
    INDEX idx_price (price),
    FULLTEXT INDEX idx_search (title, description)
) ENGINE=InnoDB;

-- =============================================
-- Lessons Table - جدول الدروس
-- =============================================
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    duration INT DEFAULT 0 COMMENT 'Duration in minutes',
    order_num INT DEFAULT 0,
    is_free TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    INDEX idx_course (course_id),
    INDEX idx_order (order_num)
) ENGINE=InnoDB;

-- =============================================
-- Enrollments Table - جدول التسجيلات
-- =============================================
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress DECIMAL(5,2) DEFAULT 0.00,
    completed_lessons TEXT DEFAULT NULL COMMENT 'Comma-separated lesson IDs',
    is_completed TINYINT(1) DEFAULT 0,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_enrollment (user_id, course_id),
    INDEX idx_user (user_id),
    INDEX idx_course (course_id),
    INDEX idx_is_completed (is_completed)
) ENGINE=InnoDB;

-- =============================================
-- Reviews Table - جدول التقييمات
-- =============================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_review (user_id, course_id),
    INDEX idx_course (course_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB;

-- =============================================
-- Cart Table - جدول السلة
-- =============================================
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_cart_item (user_id, course_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =============================================
-- Favorites Table - جدول المفضلة
-- =============================================
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_favorite (user_id, course_id),
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

-- Insert Instructor (password: password)
INSERT INTO users (name, email, password, role, is_active, email_verified_at) VALUES
('أحمد محمد', 'instructor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor', 1, NOW());

-- Insert Student (password: password)
INSERT INTO users (name, email, password, role, is_active, email_verified_at) VALUES
('علي أحمد', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1, NOW());

-- Insert Categories
INSERT INTO categories (name, description, icon) VALUES
('البرمجة', 'دورات تعليم البرمجة وتطوير البرمجيات', 'code'),
('التصميم', 'دورات التصميم الجرافيكي وتصميم واجهات المستخدم', 'palette'),
('التسويق', 'دورات التسويق الرقمي والإلكتروني', 'trending_up'),
('اللغات', 'دورات تعلم اللغات الأجنبية', 'language'),
('إدارة الأعمال', 'دورات إدارة الأعمال والمشاريع', 'business');

-- Insert Sample Courses
INSERT INTO courses (title, description, instructor_id, category_id, price, discount_price, level, duration, is_featured, is_published) VALUES
('تعلم Flutter من الصفر', 'دورة شاملة لتعلم تطوير تطبيقات الموبايل باستخدام Flutter', 2, 1, 99.99, 79.99, 'beginner', 1200, 1, 1),
('أساسيات تصميم الجرافيك', 'تعلم أساسيات التصميم باستخدام Adobe Photoshop و Illustrator', 2, 2, 79.99, NULL, 'beginner', 800, 1, 1),
('التسويق الرقمي المتقدم', 'استراتيجيات التسويق الرقمي للمحترفين', 2, 3, 149.99, 119.99, 'advanced', 1500, 1, 1),
('تطوير الويب بـ React', 'تعلم بناء تطبيقات الويب الحديثة باستخدام React.js', 2, 1, 129.99, 99.99, 'intermediate', 1000, 0, 1),
('اللغة الإنجليزية للأعمال', 'دورة متخصصة في اللغة الإنجليزية لبيئة العمل', 2, 4, 59.99, NULL, 'intermediate', 600, 0, 1);

-- Insert Sample Lessons
INSERT INTO lessons (course_id, title, description, video_url, duration, order_num, is_free) VALUES
-- Flutter Course Lessons
(1, 'مقدمة في Flutter', 'تعرف على Flutter ومميزاته', 'https://example.com/video1.mp4', 15, 1, 1),
(1, 'تثبيت بيئة التطوير', 'كيفية تثبيت Flutter و Android Studio', 'https://example.com/video2.mp4', 20, 2, 1),
(1, 'أول تطبيق Flutter', 'بناء أول تطبيق باستخدام Flutter', 'https://example.com/video3.mp4', 30, 3, 0),
(1, 'Widgets الأساسية', 'تعلم الـ Widgets الأساسية في Flutter', 'https://example.com/video4.mp4', 45, 4, 0),
(1, 'إدارة الحالة', 'مقدمة في إدارة الحالة State Management', 'https://example.com/video5.mp4', 60, 5, 0),

-- Design Course Lessons
(2, 'مقدمة في التصميم', 'أساسيات ومبادئ التصميم الجرافيكي', 'https://example.com/video6.mp4', 20, 1, 1),
(2, 'واجهة Photoshop', 'التعرف على واجهة برنامج Photoshop', 'https://example.com/video7.mp4', 25, 2, 0),
(2, 'أدوات التحديد', 'استخدام أدوات التحديد المختلفة', 'https://example.com/video8.mp4', 30, 3, 0),

-- Marketing Course Lessons
(3, 'مقدمة في التسويق الرقمي', 'أساسيات التسويق الرقمي', 'https://example.com/video9.mp4', 25, 1, 1),
(3, 'التسويق عبر وسائل التواصل', 'استراتيجيات التسويق على السوشيال ميديا', 'https://example.com/video10.mp4', 40, 2, 0);

-- Insert Sample Reviews
INSERT INTO reviews (user_id, course_id, rating, comment) VALUES
(3, 1, 5, 'دورة ممتازة ومفيدة جداً، شرح واضح ومبسط'),
(3, 2, 4, 'دورة جيدة، أتمنى المزيد من التطبيقات العملية');

-- Insert Sample Enrollments
INSERT INTO enrollments (user_id, course_id, progress, completed_lessons) VALUES
(3, 1, 40.00, '1,2'),
(3, 2, 33.33, '1');

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'منصة الدورات التعليمية'),
('site_description', 'أفضل منصة عربية للدورات التعليمية عبر الإنترنت'),
('contact_email', 'info@example.com'),
('currency', 'USD'),
('currency_symbol', '$');
