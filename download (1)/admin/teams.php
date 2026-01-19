<?php
/**
 * إدارة الفرق - لوحة التحكم
 * Team Management - Admin Panel
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');

// معالجة تبديل اللغة
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: teams.php');
    exit;
}

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $team_id = intval($_POST['team_id'] ?? 0);
    
    if ($action === 'delete' && $team_id) {
        // إزالة المستخدمين من الفريق أولاً
        $stmt = $pdo->prepare("UPDATE users SET team_id = NULL WHERE team_id = ?");
        $stmt->execute([$team_id]);
        
        // حذف الفريق
        $stmt = $pdo->prepare("DELETE FROM teams WHERE id = ?");
        $stmt->execute([$team_id]);
        
        logActivity('delete_team', "Deleted team ID: $team_id");
        flashMessage('success', __('team_deleted'));
    }
    
    if ($action === 'toggle_status' && $team_id) {
        $stmt = $pdo->prepare("UPDATE teams SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$team_id]);
        logActivity('toggle_team_status', "Toggled status for team ID: $team_id");
        flashMessage('success', __('status_updated'));
    }
    
    if ($action === 'toggle_open' && $team_id) {
        $stmt = $pdo->prepare("UPDATE teams SET is_open = IF(is_open = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$team_id]);
        logActivity('toggle_team_open', "Toggled open status for team ID: $team_id");
        flashMessage('success', __('status_updated'));
    }
    
    if ($action === 'reset_score' && $team_id) {
        $stmt = $pdo->prepare("UPDATE teams SET score = 0 WHERE id = ?");
        $stmt->execute([$team_id]);
        logActivity('reset_team_score', "Reset score for team ID: $team_id");
        flashMessage('success', __('score_reset'));
    }
    
    header('Location: teams.php');
    exit();
}

// جلب الفرق
$stmt = $pdo->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM users WHERE team_id = t.id) as member_count,
           (SELECT username FROM users WHERE id = t.captain_id) as captain_name,
           (SELECT COUNT(*) FROM solves s JOIN users u ON s.user_id = u.id WHERE u.team_id = t.id) as solve_count
    FROM teams t
    ORDER BY t.score DESC
");
$teams = $stmt->fetchAll();

$pageTitle = __('manage_teams');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Orbitron:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <a href="teams.php" class="nav-item active">🏴 <?php echo __('teams'); ?></a>
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
                <h1>🏴 <?php echo __('manage_teams'); ?></h1>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo __('team'); ?></th>
                                <th><?php echo __('captain'); ?></th>
                                <th><?php echo __('members'); ?></th>
                                <th><?php echo __('score'); ?></th>
                                <th><?php echo __('solves'); ?></th>
                                <th><?php echo __('invite_code'); ?></th>
                                <th><?php echo __('status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teams as $team): ?>
                                <tr>
                                    <td><?php echo $team['id']; ?></td>
                                    <td>
                                        <strong><?php echo sanitize($team['name']); ?></strong>
                                    </td>
                                    <td class="text-muted"><?php echo sanitize($team['captain_name'] ?? '-'); ?></td>
                                    <td><?php echo $team['member_count']; ?>/<?php echo $team['max_members']; ?></td>
                                    <td class="text-success"><?php echo number_format($team['score']); ?></td>
                                    <td><?php echo $team['solve_count']; ?></td>
                                    <td>
                                        <code style="background: var(--bg-secondary); padding: 2px 8px; border-radius: 4px;">
                                            <?php echo $team['invite_code'] ?? '-'; ?>
                                        </code>
                                    </td>
                                    <td>
                                        <?php if ($team['is_active']): ?>
                                            <span class="status-active">✓ <?php echo __('active'); ?></span>
                                        <?php else: ?>
                                            <span class="status-inactive">✗ <?php echo __('inactive'); ?></span>
                                        <?php endif; ?>
                                        <?php if ($team['is_open']): ?>
                                            <span class="badge badge-easy" style="margin-right: 5px;"><?php echo __('join_team'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline">
                                                <?php echo $team['is_active'] ? __('deactivate') : __('activate'); ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_open">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline">
                                                <?php echo $team['is_open'] ? 'إغلاق' : 'فتح'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="reset_score">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('<?php echo __('confirm_reset'); ?>')">
                                                <?php echo __('reset'); ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo __('confirm_delete'); ?>')">
                                                <?php echo __('delete'); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($teams)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <?php echo __('no_teams_yet'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
