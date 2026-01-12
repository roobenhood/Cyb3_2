<?php
/**
 * Index Page - الصفحة الرئيسية
 * ملف PHP متكامل مع HTML/CSS/JS
 */

require_once __DIR__ . '/api/config/config.php';

// جلب التصنيفات
$categoriesStmt = $pdo->query("SELECT c.*, COUNT(p.id) as products_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
    WHERE c.is_active = 1 
    GROUP BY c.id 
    ORDER BY c.sort_order 
    LIMIT 6");
$categories = $categoriesStmt->fetchAll();

// جلب المنتجات المميزة
$productsStmt = $pdo->query("SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.is_active = 1 AND p.is_featured = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8");
$featuredProducts = $productsStmt->fetchAll();

// التحقق من تسجيل الدخول
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$user = $isLoggedIn ? $_SESSION['user'] : null;

// جلب عدد عناصر السلة
$cartCount = 0;
if ($isLoggedIn) {
    $cartStmt = $pdo->prepare("SELECT COUNT(*) FROM cart_items WHERE user_id = ?");
    $cartStmt->execute([$_SESSION['user_id']]);
    $cartCount = $cartStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_NAME; ?> - أفضل منصة عربية للتسوق والدورات التعليمية عبر الإنترنت">
    <meta name="keywords" content="متجر, دورات, تعليم, برمجة, تصميم, تسويق">
    <title><?php echo APP_NAME; ?></title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <span class="material-icons">store</span>
                    <span><?php echo APP_NAME; ?></span>
                </a>

                <div class="nav-links" id="navLinks">
                    <a href="index.php" class="nav-link active">الرئيسية</a>
                    <a href="products.php" class="nav-link">المنتجات</a>
                    <a href="categories.php" class="nav-link">التصنيفات</a>
                    <a href="about.php" class="nav-link">من نحن</a>
                    <a href="contact.php" class="nav-link">اتصل بنا</a>
                </div>

                <div class="nav-actions">
                    <a href="cart.php" class="btn-icon">
                        <span class="material-icons">shopping_cart</span>
                        <span class="badge"><?php echo $cartCount; ?></span>
                    </a>

                    <?php if ($isLoggedIn): ?>
                    <div class="user-menu">
                        <button class="user-avatar">
                            <span class="material-icons">person</span>
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php"><span class="material-icons">person</span> الملف الشخصي</a>
                            <a href="orders.php"><span class="material-icons">receipt_long</span> طلباتي</a>
                            <a href="favorites.php"><span class="material-icons">favorite</span> المفضلة</a>
                            <hr>
                            <a href="logout.php"><span class="material-icons">logout</span> تسجيل الخروج</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-outline">تسجيل الدخول</a>
                        <a href="register.php" class="btn btn-primary">إنشاء حساب</a>
                    </div>
                    <?php endif; ?>
                </div>

                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <span class="material-icons">menu</span>
                </button>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">اكتشف أفضل المنتجات والدورات التعليمية</h1>
                <p class="hero-subtitle">تسوق واستثمر في نفسك مع أفضل العروض والخصومات</p>

                <form class="search-form" action="products.php" method="GET">
                    <input type="text" class="search-input" name="search" placeholder="ابحث عن منتج أو دورة...">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons">search</span>
                        بحث
                    </button>
                </form>

                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-value">+500</span>
                        <span class="stat-label">منتج ودورة</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">+10,000</span>
                        <span class="stat-label">عميل سعيد</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">+100</span>
                        <span class="stat-label">علامة تجارية</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="section categories-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">تصفح التصنيفات</h2>
                <a href="categories.php" class="btn btn-outline">عرض الكل</a>
            </div>

            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo $category['id']; ?>" class="category-card">
                    <div class="category-icon">
                        <span class="material-icons"><?php echo htmlspecialchars($category['icon'] ?: 'category'); ?></span>
                    </div>
                    <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="category-count"><?php echo $category['products_count']; ?> منتج</p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="section featured-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">المنتجات المميزة</h2>
                <a href="products.php?featured=1" class="btn btn-outline">عرض الكل</a>
            </div>

            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-thumbnail">
                        <img src="<?php echo htmlspecialchars($product['thumbnail'] ?: 'images/default-product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if ($product['discount_price']): ?>
                        <span class="product-badge">خصم</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-content">
                        <span class="product-category"><?php echo htmlspecialchars($product['category_name'] ?: ''); ?></span>
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-meta">
                            <div class="product-rating">
                                <span class="material-icons">star</span>
                                <span><?php echo number_format($product['rating'] ?: 0, 1); ?></span>
                            </div>
                            <div class="product-price">
                                <?php if ($product['discount_price']): ?>
                                <span class="original-price"><?php echo formatPrice($product['price']); ?></span>
                                <?php echo formatPrice($product['discount_price']); ?>
                                <?php else: ?>
                                <?php echo formatPrice($product['price']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section features-section">
        <div class="container">
            <h2 class="section-title text-center">لماذا تختارنا؟</h2>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-icons">verified</span>
                    </div>
                    <h3 class="feature-title">جودة مضمونة</h3>
                    <p class="feature-description">منتجات أصلية ومضمونة 100%</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-icons">local_shipping</span>
                    </div>
                    <h3 class="feature-title">شحن سريع</h3>
                    <p class="feature-description">توصيل سريع لجميع مناطق المملكة</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-icons">payment</span>
                    </div>
                    <h3 class="feature-title">دفع آمن</h3>
                    <p class="feature-description">طرق دفع متعددة وآمنة</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <span class="material-icons">support_agent</span>
                    </div>
                    <h3 class="feature-title">دعم متواصل</h3>
                    <p class="feature-description">فريق دعم متاح على مدار الساعة</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>ابدأ التسوق الآن</h2>
                <p>سجل واحصل على خصم 10% على طلبك الأول</p>
                <a href="register.php" class="btn btn-white">سجل مجاناً</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3 class="footer-title"><?php echo APP_NAME; ?></h3>
                    <p>أفضل منصة عربية للتسوق والتعلم عبر الإنترنت</p>
                    <div class="social-links">
                        <a href="#"><span class="material-icons">facebook</span></a>
                        <a href="#"><span class="material-icons">twitter</span></a>
                        <a href="#"><span class="material-icons">instagram</span></a>
                        <a href="#"><span class="material-icons">youtube</span></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                        <li><a href="products.php">المنتجات</a></li>
                        <li><a href="categories.php">التصنيفات</a></li>
                        <li><a href="about.php">من نحن</a></li>
                        <li><a href="contact.php">اتصل بنا</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3 class="footer-title">الدعم</h3>
                    <ul class="footer-links">
                        <li><a href="faq.php">الأسئلة الشائعة</a></li>
                        <li><a href="privacy.php">سياسة الخصوصية</a></li>
                        <li><a href="terms.php">الشروط والأحكام</a></li>
                        <li><a href="shipping.php">الشحن والتوصيل</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3 class="footer-title">تواصل معنا</h3>
                    <ul class="contact-info">
                        <li><span class="material-icons">email</span> info@example.com</li>
                        <li><span class="material-icons">phone</span> +966 12 345 6789</li>
                        <li><span class="material-icons">location_on</span> الرياض، السعودية</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts - مبسط بدون الاعتماد الكبير على JS -->
    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            document.getElementById('navLinks')?.classList.toggle('active');
        });

        // User dropdown
        document.querySelector('.user-avatar')?.addEventListener('click', function() {
            this.parentElement.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                document.querySelectorAll('.user-menu').forEach(menu => menu.classList.remove('active'));
            }
        });
    </script>
</body>
</html>
