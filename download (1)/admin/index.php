<?php
/**
 * لوحة التحكم الرئيسية
 * Admin Dashboard
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');

// معالجة تبديل اللغة
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: index.php');
    exit;
}

// إحصائيات
$stats = [];
$stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['teams'] = $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn();
$stats['challenges'] = $pdo->query("SELECT COUNT(*) FROM challenges")->fetchColumn();
$stats['solves'] = $pdo->query("SELECT COUNT(*) FROM solves")->fetchColumn();
$stats['submissions'] = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();

// آخر المستخدمين
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// آخر الحلول
$stmt = $pdo->prepare("
    SELECT s.*, u.username, 
           CASE WHEN ? = 'ar' THEN c.name_ar ELSE c.name_en END as challenge_name,
           c.points
    FROM solves s
    JOIN users u ON s.user_id = u.id
    JOIN challenges c ON s.challenge_id = c.id
    ORDER BY s.solved_at DESC
    LIMIT 10
");
$stmt->execute([$lang]);
$recent_solves = $stmt->fetchAll();

$pageTitle = __('dashboard');
$currentTheme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>" data-theme="<?php echo $currentTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="logo">
                    <span class="logo-icon">🛡️</span>
                    <span class="logo-text">AlwaniCTF</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">📊 <?php echo __('dashboard'); ?></a>
                <a href="challenges.php" class="nav-item">🚩 <?php echo __('challenges'); ?></a>
                <a href="categories.php" class="nav-item">📁 <?php echo __('manage_categories'); ?></a>
                <a href="users.php" class="nav-item">👥 <?php echo __('users'); ?></a>
                <a href="teams.php" class="nav-item">🏴 <?php echo __('teams'); ?></a>
                <a href="notifications.php" class="nav-item">🔔 <?php echo __('notifications'); ?></a>
                <a href="settings.php" class="nav-item">⚙️ <?php echo __('settings'); ?></a>
                <a href="security_settings.php" class="nav-item">🔐 <?php echo __('security_settings'); ?></a>
                <a href="roles.php" class="nav-item">👑 <?php echo __('roles_management'); ?></a>
                <hr class="nav-divider">
                <a href="../index.php" class="nav-item">🏠 <?php echo __('home'); ?></a>
                <a href="../logout.php" class="nav-item logout">🚪 <?php echo __('logout'); ?></a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="lang-switch">
                    <a href="?lang=ar" class="<?php echo $lang === 'ar' ? 'active' : ''; ?>">العربية</a>
                    <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
                </div>
            </div>
        </aside>
        
        <main class="admin-main">
            <div class="page-header">
                <h1>📊 <?php echo __('dashboard'); ?></h1>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['users']; ?></div>
                    <div class="stat-label"><?php echo __('users'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['teams']; ?></div>
                    <div class="stat-label"><?php echo __('teams'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['challenges']; ?></div>
                    <div class="stat-label"><?php echo __('challenges'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['solves']; ?></div>
                    <div class="stat-label"><?php echo __('solves'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['submissions']; ?></div>
                    <div class="stat-label"><?php echo __('submissions'); ?></div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="card">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><?php echo __('user'); ?></th>
                                    <th><?php echo __('email'); ?></th>
                                    <th><?php echo __('date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><strong><?php echo sanitize($user['username']); ?></strong></td>
                                        <td class="text-muted"><?php echo sanitize($user['email']); ?></td>
                                        <td class="text-muted"><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="users.php" class="btn btn-outline btn-sm"><?php echo __('view_all'); ?></a>
                </div>
                
                <div class="card">
                    <h3 class="card-title">🚩 <?php echo __('recent_solves'); ?></h3>
                    <div class="activity-list">
                        <?php foreach ($recent_solves as $solve): ?>
                            <div class="activity-item">
                                <div class="activity-info">
                                    <strong><?php echo sanitize($solve['username']); ?></strong>
                                    <span class="text-muted"> <?php echo __('solved'); ?> </span>
                                    <span class="text-cyan"><?php echo sanitize($solve['challenge_name']); ?></span>
                                </div>
                                <div class="activity-points">+<?php echo $solve['points']; ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($recent_solves)): ?>
                            <p class="text-muted text-center"><?php echo __('no_solves_yet'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
