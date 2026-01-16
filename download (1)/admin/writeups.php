<?php
/**
 * إدارة الـ Writeups - لوحة التحكم
 * Writeups Management - Admin Panel
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');

// معالجة تبديل اللغة
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: writeups.php');
    exit;
}

// معالجة Writeup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $writeup_id = intval($_POST['writeup_id']);
        $action = sanitize($_POST['action']);
        
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE writeups SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $writeup_id]);
            logActivity('approve_writeup', "Approved writeup ID: $writeup_id");
            flashMessage('success', __('writeup_approved'));
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE writeups SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$writeup_id]);
            logActivity('reject_writeup', "Rejected writeup ID: $writeup_id");
            flashMessage('success', __('writeup_rejected'));
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM writeups WHERE id = ?");
            $stmt->execute([$writeup_id]);
            logActivity('delete_writeup', "Deleted writeup ID: $writeup_id");
            flashMessage('success', __('writeup_deleted'));
        }
    }
    header('Location: writeups.php');
    exit;
}

// جلب الـ Writeups
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';
$nameCol = $lang === 'ar' ? 'name_ar' : 'name_en';

$stmt = $pdo->prepare("
    SELECT w.*, u.username, c.$nameCol as challenge_name
    FROM writeups w
    JOIN users u ON w.user_id = u.id
    JOIN challenges c ON w.challenge_id = c.id
    WHERE w.status = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$status_filter]);
$writeups = $stmt->fetchAll();

$pageTitle = __('manage_writeups');
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
                <a href="teams.php" class="nav-item">🏴 <?php echo __('teams'); ?></a>
                <a href="writeups.php" class="nav-item active">📝 <?php echo __('writeups'); ?></a>
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
                <h1>📝 <?php echo __('manage_writeups'); ?></h1>
            </div>
            
            <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                <a href="?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-neon' : 'btn-outline'; ?>">
                    ⏳ <?php echo __('pending'); ?>
                </a>
                <a href="?status=approved" class="btn <?php echo $status_filter === 'approved' ? 'btn-neon' : 'btn-outline'; ?>">
                    ✅ <?php echo __('approved'); ?>
                </a>
                <a href="?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? 'btn-neon' : 'btn-outline'; ?>">
                    ❌ <?php echo __('rejected'); ?>
                </a>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo __('user'); ?></th>
                                <th><?php echo __('challenge'); ?></th>
                                <th><?php echo __('title'); ?></th>
                                <th><?php echo __('created_at'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($writeups)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted"><?php echo __('no_writeups'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($writeups as $writeup): ?>
                                    <tr>
                                        <td><strong><?php echo sanitize($writeup['username']); ?></strong></td>
                                        <td><?php echo sanitize($writeup['challenge_name']); ?></td>
                                        <td>
                                            <a href="../view_writeup.php?id=<?php echo $writeup['id']; ?>" target="_blank" class="text-cyan">
                                                <?php echo sanitize($writeup['title']); ?>
                                            </a>
                                        </td>
                                        <td class="text-muted"><?php echo date('Y-m-d H:i', strtotime($writeup['created_at'])); ?></td>
                                        <td class="actions">
                                            <?php if ($status_filter === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="writeup_id" value="<?php echo $writeup['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-sm btn-success">✅ <?php echo __('approve'); ?></button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="writeup_id" value="<?php echo $writeup['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-sm btn-warning">❌ <?php echo __('reject'); ?></button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="writeup_id" value="<?php echo $writeup['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo __('confirm_delete'); ?>')">
                                                    🗑️ <?php echo __('delete'); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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
