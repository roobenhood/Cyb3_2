<?php
/**
 * إدارة الصلاحيات والأدوار
 * Roles & Permissions Management
 * Only for Super Admin
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');
$currentUser = getCurrentUser();

// التحقق من صلاحية السوبر أدمن
$isSuperAdmin = ($currentUser['role'] === 'super_admin' || $currentUser['id'] == 1);

if (!$isSuperAdmin) {
    flashMessage('error', __('super_admin_required'));
    header('Location: index.php');
    exit;
}

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_role') {
        $userId = intval($_POST['user_id'] ?? 0);
        $newRole = sanitize($_POST['role'] ?? 'user');
        
        // لا يمكن تغيير صلاحيات المستخدم رقم 1 (السوبر أدمن الأصلي)
        if ($userId === 1) {
            flashMessage('error', __('cannot_change_super_admin'));
        } elseif (in_array($newRole, ['user', 'admin', 'super_admin'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$newRole, $userId])) {
                logActivity('role_change', "User $userId role changed to $newRole");
                flashMessage('success', __('role_updated'));
            }
        }
    }
    
    header('Location: roles.php');
    exit;
}

// جلب المستخدمين مع صلاحياتهم
$stmt = $pdo->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM solves WHERE user_id = u.id) as solve_count
    FROM users u 
    ORDER BY 
        CASE u.role 
            WHEN 'super_admin' THEN 1 
            WHEN 'admin' THEN 2 
            ELSE 3 
        END,
        u.created_at DESC
");
$users = $stmt->fetchAll();

// إحصائيات
$roleStats = [
    'super_admin' => 0,
    'admin' => 0,
    'user' => 0
];
foreach ($users as $user) {
    $roleStats[$user['role']] = ($roleStats[$user['role']] ?? 0) + 1;
}

$pageTitle = __('roles_management');
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
                <a href="users.php" class="nav-item">👥 <?php echo __('users'); ?></a>
                <a href="teams.php" class="nav-item">🏴 <?php echo __('teams'); ?></a>
                <a href="notifications.php" class="nav-item">🔔 <?php echo __('notifications'); ?></a>
                <a href="settings.php" class="nav-item">⚙️ <?php echo __('settings'); ?></a>
                <a href="security_settings.php" class="nav-item">🔐 <?php echo __('security_settings'); ?></a>
                <a href="roles.php" class="nav-item active">👑 <?php echo __('roles_management'); ?></a>
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
                <h1>👑 <?php echo __('roles_management'); ?></h1>
                <p class="page-description"><?php echo __('roles_description'); ?></p>
            </div>
            
            <!-- إحصائيات الأدوار -->
            <div class="roles-stats">
                <div class="role-stat super-admin">
                    <span class="role-icon">👑</span>
                    <span class="role-count"><?php echo $roleStats['super_admin']; ?></span>
                    <span class="role-label"><?php echo __('super_admins'); ?></span>
                </div>
                <div class="role-stat admin">
                    <span class="role-icon">🛡️</span>
                    <span class="role-count"><?php echo $roleStats['admin']; ?></span>
                    <span class="role-label"><?php echo __('admins'); ?></span>
                </div>
                <div class="role-stat user">
                    <span class="role-icon">👤</span>
                    <span class="role-count"><?php echo $roleStats['user']; ?></span>
                    <span class="role-label"><?php echo __('users'); ?></span>
                </div>
            </div>
            
            <!-- قائمة المستخدمين -->
            <div class="settings-card">
                <h3>👥 <?php echo __('users_list'); ?></h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo __('user'); ?></th>
                                <th><?php echo __('email'); ?></th>
                                <th><?php echo __('current_role'); ?></th>
                                <th><?php echo __('solves'); ?></th>
                                <th><?php echo __('joined'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="user-row <?php echo $user['role']; ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div class="user-info-cell">
                                        <strong><?php echo sanitize($user['username']); ?></strong>
                                        <?php if ($user['id'] === 1): ?>
                                            <span class="badge badge-gold">👑 <?php echo __('founder'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-muted"><?php echo sanitize($user['email']); ?></td>
                                <td>
                                    <?php
                                    $roleClass = $user['role'];
                                    $roleIcon = $user['role'] === 'super_admin' ? '👑' : ($user['role'] === 'admin' ? '🛡️' : '👤');
                                    ?>
                                    <span class="role-badge <?php echo $roleClass; ?>">
                                        <?php echo $roleIcon; ?> <?php echo __($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['solve_count']; ?></td>
                                <td class="text-muted"><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] !== 1 && $user['id'] !== $currentUser['id']): ?>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="role" class="form-select role-select" onchange="this.form.submit()">
                                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>
                                                👤 <?php echo __('user'); ?>
                                            </option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>
                                                🛡️ <?php echo __('admin'); ?>
                                            </option>
                                            <option value="super_admin" <?php echo $user['role'] === 'super_admin' ? 'selected' : ''; ?>>
                                                👑 <?php echo __('super_admin'); ?>
                                            </option>
                                        </select>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
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