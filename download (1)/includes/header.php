<!DOCTYPE html>
<?php 
$currentLang = getCurrentLanguage();
$isRTL = ($currentLang === 'ar');
$currentTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>" data-theme="<?php echo $currentTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo getSetting('site_description', 'AlwaniCTF - منصة مسابقات الأمن السيبراني والتحديات الأمنية. تعلم الاختراق الأخلاقي وتطوير مهاراتك في CTF'); ?>">
    <meta name="keywords" content="CTF, Capture The Flag, Cybersecurity, Hacking, Security Challenges, الأمن السيبراني, تحديات أمنية, اختراق أخلاقي, AlwaniCTF">
    <meta name="author" content="AlwaniCTF">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#00ff88">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo getSetting('site_description', 'AlwaniCTF - منصة مسابقات الأمن السيبراني'); ?>">
    <meta property="og:image" content="<?php echo getSetting('site_og_image', ''); ?>">
    <meta property="og:locale" content="<?php echo $currentLang === 'ar' ? 'ar_SA' : 'en_US'; ?>">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo getSetting('site_description', 'AlwaniCTF - منصة مسابقات الأمن السيبراني'); ?>">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Preload fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Base URL for relative paths -->
    <?php
    $baseHref = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($baseHref === '' || $baseHref === '.' || $baseHref === '/') {
        $baseHref = '';
    }
    $baseHref = $baseHref . '/';
    ?>
    <base href="<?php echo $baseHref; ?>">

    <!-- Main CSS (includes all other CSS files) -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?php echo time(); ?>">
    
    <!-- Prevent caching issues in development -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo getSetting('site_favicon', 'assets/images/favicon.png'); ?>">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🇾🇪</text></svg>">
    <link rel="apple-touch-icon" href="<?php echo getSetting('site_favicon', 'assets/images/favicon.png'); ?>">
    
    <script>
        // تحميل الوضع المحفوظ مباشرة لمنع الوميض
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="page-enter">
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <span class="logo-icon">🛡️</span>
                <span class="logo-text">AlwaniCTF</span>
            </a>
            <div class="nav-links">
                <a href="challenges.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'challenges.php' ? 'active' : ''; ?>">
                    🚩 <?php echo __('challenges'); ?>
                </a>
                <a href="scoreboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'scoreboard.php' ? 'active' : ''; ?>">
                    🏆 <?php echo __('scoreboard'); ?>
                </a>
                <a href="teams.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'teams.php' ? 'active' : ''; ?>">
                    👥 <?php echo __('teams'); ?>
                </a>
                <?php if (isLoggedIn()): ?>
                    <?php 
                    // حساب عدد الإشعارات غير المقروءة
                    $unread_count = 0;
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM notifications n 
                        LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.user_id = ?
                        WHERE n.is_active = 1 AND nr.id IS NULL
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $unread_count = $stmt->fetchColumn();
                    
                    // التحقق من صلاحيات الأدمن من قاعدة البيانات
                    $currentUserData = getCurrentUser();
                    $isAdminUser = ($currentUserData && ($currentUserData['role'] === 'admin' || $currentUserData['role'] === 'super_admin'));
                    ?>
                    <?php if ($isAdminUser): ?>
                    <a href="admin/index.php" class="btn btn-admin">
                        ⚙️ <?php echo __('admin_panel'); ?>
                    </a>
                    <?php endif; ?>
                    <a href="notifications.php" class="nav-link notification-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>">
                        🔔
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" class="nav-link"><?php echo sanitize($_SESSION['username']); ?></a>
                    <a href="logout.php" class="btn btn-outline"><?php echo __('logout'); ?></a>
                <?php else: ?>
                    <a href="login.php" class="nav-link"><?php echo __('login'); ?></a>
                    <a href="register.php" class="btn btn-neon"><?php echo __('register'); ?></a>
                <?php endif; ?>
                
                <!-- Theme & Language Toggle -->
                <div class="nav-controls">
                    <button class="theme-toggle" onclick="toggleTheme()" title="<?php echo __('toggle_theme'); ?>">
                        <span class="theme-icon">🌙</span>
                    </button>
                    <div class="lang-dropdown">
                        <button class="lang-toggle" onclick="toggleLangMenu()">
                            <?php echo $currentLang === 'ar' ? '🇾🇪' : '🇺🇸'; ?>
                        </button>
                        <div class="lang-menu" id="langMenu">
                            <?php 
                            $currentUrl = $_SERVER['REQUEST_URI'];
                            $urlParts = parse_url($currentUrl);
                            $path = $urlParts['path'] ?? '';
                            parse_str($urlParts['query'] ?? '', $queryParams);
                            $queryParams['lang'] = 'ar';
                            $arUrl = $path . '?' . http_build_query($queryParams);
                            $queryParams['lang'] = 'en';
                            $enUrl = $path . '?' . http_build_query($queryParams);
                            ?>
                            <a href="<?php echo htmlspecialchars($arUrl); ?>" class="<?php echo $currentLang === 'ar' ? 'active' : ''; ?>">🇾🇪 YE</a>
                            <a href="<?php echo htmlspecialchars($enUrl); ?>" class="<?php echo $currentLang === 'en' ? 'active' : ''; ?>">🇺🇸 English</a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">☰</button>
        </div>
    </nav>
    
    <div class="mobile-menu" id="mobileMenu">
        <a href="challenges.php">🚩 <?php echo __('challenges'); ?></a>
        <a href="scoreboard.php">🏆 <?php echo __('scoreboard'); ?></a>
        <a href="teams.php">👥 <?php echo __('teams'); ?></a>
        <?php if (isLoggedIn()): ?>
            <?php if ($isAdminUser): ?>
            <a href="admin/index.php" class="mobile-admin-link">⚙️ <?php echo __('admin_panel'); ?></a>
            <?php endif; ?>
            <a href="notifications.php">🔔 <?php echo __('notifications'); ?></a>
            <a href="profile.php"><?php echo sanitize($_SESSION['username']); ?></a>
            <a href="logout.php"><?php echo __('logout'); ?></a>
        <?php else: ?>
            <a href="login.php"><?php echo __('login'); ?></a>
            <a href="register.php"><?php echo __('register'); ?></a>
        <?php endif; ?>
        <div class="mobile-controls">
            <button class="theme-toggle" onclick="toggleTheme()">
                <span class="theme-icon">🌙</span> <?php echo __('toggle_theme'); ?>
            </button>
            <div class="mobile-lang">
                <?php 
                if (!isset($arUrl)) {
                    $currentUrl = $_SERVER['REQUEST_URI'];
                    $urlParts = parse_url($currentUrl);
                    $path = $urlParts['path'] ?? '';
                    parse_str($urlParts['query'] ?? '', $queryParams);
                    $queryParams['lang'] = 'ar';
                    $arUrl = $path . '?' . http_build_query($queryParams);
                    $queryParams['lang'] = 'en';
                    $enUrl = $path . '?' . http_build_query($queryParams);
                }
                ?>
                <a href="<?php echo htmlspecialchars($arUrl); ?>" class="<?php echo $currentLang === 'ar' ? 'active' : ''; ?>">🇾🇪 YE</a>
                <a href="<?php echo htmlspecialchars($enUrl); ?>" class="<?php echo $currentLang === 'en' ? 'active' : ''; ?>">🇺🇸 EN</a>
            </div>
        </div>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo $flash['message']; ?>
        </div>
    <?php endif; ?>

    <main class="main-content">
