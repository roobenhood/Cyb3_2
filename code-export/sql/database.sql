-- =====================================================
-- قاعدة بيانات منصة الكورسات التعليمية
-- =====================================================

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS courses_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE courses_db;

-- =====================================================
-- جدول المستخدمين
-- =====================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(500) NULL,
    bio TEXT NULL,
    role ENUM('student', 'instructor', 'admin') DEFAULT 'student',
    email_verified_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- =====================================================
-- جدول توكنات المستخدمين
-- =====================================================
CREATE TABLE user_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- جدول التصنيفات
-- =====================================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    name_en VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50) NULL,
    description TEXT NULL,
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- =====================================================
-- جدول الكورسات
-- =====================================================
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    short_description VARCHAR(500) NULL,
    thumbnail VARCHAR(500) NULL,
    preview_video VARCHAR(500) NULL,
    price DECIMAL(10, 2) DEFAULT 0.00,
    discount_price DECIMAL(10, 2) NULL,
    duration VARCHAR(50) NULL COMMENT 'المدة الإجمالية',
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    language VARCHAR(50) DEFAULT 'العربية',
    requirements TEXT NULL COMMENT 'المتطلبات المسبقة',
    what_you_learn TEXT NULL COMMENT 'ماذا ستتعلم',
    status ENUM('draft', 'pending', 'published', 'archived') DEFAULT 'draft',
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_instructor (instructor_id),
    INDEX idx_category (category_id),
    FULLTEXT INDEX idx_search (title, description)
) ENGINE=InnoDB;

-- =====================================================
-- جدول الدروس
-- =====================================================
CREATE TABLE lessons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    video_url VARCHAR(500) NULL,
    video_duration INT NULL COMMENT 'المدة بالثواني',
    duration VARCHAR(50) NULL,
    content TEXT NULL COMMENT 'محتوى نصي إضافي',
    attachments JSON NULL COMMENT 'ملفات مرفقة',
    is_preview BOOLEAN DEFAULT FALSE COMMENT 'هل الدرس مجاني للمعاينة',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course (course_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB;

-- =====================================================
-- جدول التسجيل في الكورسات
-- =====================================================
CREATE TABLE enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    payment_id INT NULL,
    progress INT DEFAULT 0 COMMENT 'نسبة الإكمال',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    certificate_issued BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    INDEX idx_user (user_id),
    INDEX idx_course (course_id)
) ENGINE=InnoDB;

-- =====================================================
-- جدول تقدم الدروس
-- =====================================================
CREATE TABLE lesson_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    watch_time INT DEFAULT 0 COMMENT 'وقت المشاهدة بالثواني',
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    last_watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (user_id, lesson_id),
    INDEX idx_user (user_id),
    INDEX idx_lesson (lesson_id)
) ENGINE=InnoDB;

-- =====================================================
-- جدول المدفوعات
-- =====================================================
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    transaction_id VARCHAR(255) NULL,
    payment_details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_transaction (transaction_id)
) ENGINE=InnoDB;

-- =====================================================
-- جدول التقييمات
-- =====================================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NULL,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, course_id),
    INDEX idx_course (course_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB;

-- =====================================================
-- جدول السلة
-- =====================================================
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, course_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- جدول المفضلة
-- =====================================================
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, course_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =====================================================
-- جدول الشهادات
-- =====================================================
CREATE TABLE certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_number VARCHAR(50) NOT NULL UNIQUE,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_url VARCHAR(500) NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_number (certificate_number)
) ENGINE=InnoDB;

-- =====================================================
-- جدول الكوبونات
-- =====================================================
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    min_purchase DECIMAL(10, 2) DEFAULT 0,
    max_uses INT NULL,
    used_count INT DEFAULT 0,
    course_id INT NULL COMMENT 'NULL يعني لجميع الكورسات',
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =====================================================
-- إدراج بيانات تجريبية
-- =====================================================

-- إضافة مستخدم admin
INSERT INTO users (name, email, password, role) VALUES 
('المدير', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('أحمد المدرب', 'instructor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'instructor'),
('محمد الطالب', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');
-- كلمة المرور: password

-- إضافة تصنيفات
INSERT INTO categories (name, name_en, slug, icon) VALUES 
('البرمجة', 'Programming', 'programming', 'code'),
('التصميم', 'Design', 'design', 'palette'),
('التسويق', 'Marketing', 'marketing', 'trending-up'),
('اللغات', 'Languages', 'languages', 'globe'),
('الأعمال', 'Business', 'business', 'briefcase');

-- إضافة كورسات تجريبية
INSERT INTO courses (instructor_id, category_id, title, slug, description, price, level, status, is_featured) VALUES 
(2, 1, 'كورس Flutter الشامل', 'flutter-course', 'تعلم تطوير تطبيقات الموبايل باستخدام Flutter من الصفر إلى الاحتراف', 49.99, 'beginner', 'published', TRUE),
(2, 1, 'تطوير واجهات ويب بـ React', 'react-course', 'أتقن React وقم ببناء تطبيقات ويب احترافية', 39.99, 'intermediate', 'published', TRUE),
(2, 2, 'تصميم UI/UX احترافي', 'ui-ux-course', 'تعلم أساسيات ومبادئ تصميم واجهات المستخدم', 29.99, 'beginner', 'published', FALSE),
(2, 3, 'التسويق الرقمي', 'digital-marketing', 'استراتيجيات التسويق الرقمي الفعالة', 44.99, 'beginner', 'published', TRUE);

-- إضافة دروس
INSERT INTO lessons (course_id, title, duration, is_preview, sort_order) VALUES 
(1, 'مقدمة عن Flutter', '10:00', TRUE, 1),
(1, 'تثبيت بيئة العمل', '15:00', TRUE, 2),
(1, 'أول تطبيق Flutter', '20:00', FALSE, 3),
(1, 'Widgets الأساسية', '25:00', FALSE, 4),
(1, 'State Management', '30:00', FALSE, 5);
