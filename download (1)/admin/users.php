<?php
/**
 * إدارة المستخدمين - لوحة التحكم
 * User Management - Admin Panel
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');

// معالجة تبديل اللغة
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: users.php');
    exit;
}

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($action === 'delete' && $user_id) {
        // لا يمكن حذف الأدمن الحالي
        if ($user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            logActivity('delete_user', "Deleted user ID: $user_id");
            flashMessage('success', __('user_deleted'));
        }
    }
    
    if ($action === 'toggle_role' && $user_id) {
        if ($user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE id = ?");
            $stmt->execute([$user_id]);
            logActivity('toggle_role', "Toggled role for user ID: $user_id");
            flashMessage('success', __('role_updated'));
        }
    }
    
    if ($action === 'reset_score' && $user_id) {
        $stmt = $pdo->prepare("UPDATE users SET score = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        $stmt = $pdo->prepare("DELETE FROM solves WHERE user_id = ?");
        $stmt->execute([$user_id]);
        logActivity('reset_score', "Reset score for user ID: $user_id");
        flashMessage('success', __('score_reset'));
    }
    
    if ($action === 'toggle_active' && $user_id) {
        if ($user_id != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
            $stmt->execute([$user_id]);
            logActivity('toggle_active', "Toggled active for user ID: $user_id");
            flashMessage('success', __('status_updated'));
        }
    }
    
    header('Location: users.php');
    exit();
}

// جلب المستخدمين
$stmt = $pdo->query("
    SELECT u.*, t.name as team_name,
           (SELECT COUNT(*) FROM solves WHERE user_id = u.id) as solve_count
    FROM users u
    LEFT JOIN teams t ON u.team_id = t.id
    ORDER BY u.score DESC
");
$users = $stmt->fetchAll();

$pageTitle = __('manage_users');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="index.php" class="nav-item">📊 <?php echo __('dashboard'); ?></a>
                <a href="challenges.php" class="nav-item">🚩 <?php echo __('challenges'); ?></a>
                <a href="categories.php" class="nav-item">📁 <?php echo __('manage_categories'); ?></a>
                <a href="users.php" class="nav-item active">👥 <?php echo __('users'); ?></a>
                <a href="teams.php" class="nav-item">🏴 <?php echo __('teams'); ?></a>
                <a href="notifications.php" class="nav-item">🔔 <?php echo __('notifications'); ?></a>
                <a href="settings.php" class="nav-item">⚙️ <?php echo __('settings'); ?></a>
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
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>👥 <?php echo __('manage_users'); ?></h1>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo __('user'); ?></th>
                                <th><?php echo __('email'); ?></th>
                                <th><?php echo __('team'); ?></th>
                                <th><?php echo __('score'); ?></th>
                                <th><?php echo __('solves'); ?></th>
                                <th><?php echo __('role'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><strong><?php echo sanitize($user['username']); ?></strong></td>
                                    <td class="text-muted"><?php echo sanitize($user['email']); ?></td>
                                    <td><?php echo $user['team_name'] ? sanitize($user['team_name']) : '-'; ?></td>
                                    <td class="text-success"><?php echo number_format($user['score']); ?></td>
                                    <td><?php echo $user['solve_count']; ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge badge-insane"><?php echo __('admin'); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-easy"><?php echo __('user'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['is_active']): ?>
                                            <span class="status-active">✓ <?php echo __('active'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive">✗ <?php echo __('inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline">
                                                    <?php echo $user['role'] === 'admin' ? __('remove_admin') : __('make_admin'); ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline">
                                                    <?php echo $user['is_active'] ? __('deactivate') : __('activate'); ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reset_score">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('<?php echo __('confirm_reset'); ?>')">
                                                    <?php echo __('reset'); ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo __('confirm_delete'); ?>')">
                                                    <?php echo __('delete'); ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
