<?php
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? SITE_NAME); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php if (($isAdmin ?? false) === true): ?>
<header class="header admin-header">
    <div class="container header-content">
        <a href="admin.php" class="logo">🔧 لوحة التحكم</a>
        <nav class="nav-menu">
            <a href="admin.php" class="nav-link <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">الرئيسية</a>
            <a href="admin.php?page=courses" class="nav-link <?php echo ($currentPage ?? '') === 'courses' ? 'active' : ''; ?>">إدارة الكورسات</a>
            <a href="admin.php?page=users" class="nav-link <?php echo ($currentPage ?? '') === 'users' ? 'active' : ''; ?>">إدارة المستخدمين</a>
            <a href="index.php" class="nav-link">العودة للموقع</a>
        </nav>
        <div class="user-menu">
            <span class="user-name">
                <?php echo htmlspecialchars(Session::getUserName()); ?>
                <span class="role-badge admin">أدمن</span>
            </span>
            <a href="auth.php?action=logout" class="btn btn-danger btn-sm">خروج</a>
        </div>
    </div>
</header>
<?php else: ?>

<header class="header">
    <div class="container header-content">
        <a href="index.php" class="logo">📚 <?php echo SITE_NAME; ?></a>
        <nav class="nav-menu">
            <a href="index.php" class="nav-link <?php echo ($currentPage ?? '') === 'home' ? 'active' : ''; ?>">الرئيسية</a>
            <?php if (Session::isLoggedIn()): ?>
                <a href="courses.php" class="nav-link <?php echo ($currentPage ?? '') === 'my-courses' ? 'active' : ''; ?>">كورساتي</a>
                <?php if (Session::isAdmin()): ?>
                    <a href="admin.php" class="nav-link">لوحة التحكم</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="user-menu">
            <?php if (Session::isLoggedIn()): ?>
                <span class="user-name">
                    <?php echo htmlspecialchars(Session::getUserName()); ?>
                    <?php if (Session::isAdmin()): ?>
                        <span class="role-badge admin">أدمن</span>
                    <?php endif; ?>
                </span>
                <a href="auth.php?action=logout" class="btn btn-danger btn-sm">خروج</a>
            <?php else: ?>
                <a href="auth.php?action=login" class="btn btn-primary btn-sm">دخول</a>
                <a href="auth.php?action=register" class="btn btn-secondary btn-sm">تسجيل</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php endif; ?>

<main class="main-content">
    <div class="container">
        <?php 
        if ($flash = Session::getFlash()): 
        ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php 
        echo $content ?? '';
        ?>
    </div>
</main>

</body>
</html>
