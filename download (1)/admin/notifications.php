<?php
/**
 * إدارة الإشعارات - لوحة التحكم
 * Notifications Management - Admin Panel
 */
require_once '../config.php';
requireAdmin();

$lang = getCurrentLanguage();
$isRTL = ($lang === 'ar');

// معالجة تبديل اللغة
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: notifications.php');
    exit;
}

// إضافة إشعار جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_notification'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $title_en = sanitize($_POST['title_en']);
        $title_ar = sanitize($_POST['title_ar']);
        $content_en = sanitize($_POST['content_en']);
        $content_ar = sanitize($_POST['content_ar']);
        $type = sanitize($_POST['type']);
        $icon = sanitize($_POST['icon'] ?: '📢');
        $target = sanitize($_POST['target']);
        
        $stmt = $pdo->prepare("INSERT INTO notifications (title_en, title_ar, content_en, content_ar, type, icon, target, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title_en, $title_ar, $content_en, $content_ar, $type, $icon, $target, $_SESSION['user_id']]);
        
        logActivity('create_notification', "Created notification: $title_en");
        flashMessage('success', __('notification_created'));
    }
    header('Location: notifications.php');
    exit;
}

// حذف إشعار
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notification'])) {
    $notification_id = intval($_POST['notification_id'] ?? 0);
    if ($notification_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->execute([$notification_id]);
        logActivity('delete_notification', "Deleted notification ID: $notification_id");
        flashMessage('success', __('notification_deleted'));
    }
    header('Location: notifications.php');
    exit;
}

// جلب الإشعارات
$stmt = $pdo->query("SELECT n.*, u.username as created_by_name FROM notifications n LEFT JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC");
$notifications = $stmt->fetchAll();

$pageTitle = __('manage_notifications');
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
                <a href="notifications.php" class="nav-item active">🔔 <?php echo __('notifications'); ?></a>
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
                <h1>🔔 <?php echo __('manage_notifications'); ?></h1>
            </div>
            
            <div style="margin-bottom: 20px;">
                <button class="btn btn-neon" onclick="openModal()">
                    ➕ <?php echo __('add_notification'); ?>
                </button>
            </div>
            
            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><?php echo __('icon'); ?></th>
                                <th><?php echo __('title'); ?></th>
                                <th><?php echo __('type'); ?></th>
                                <th><?php echo __('target'); ?></th>
                                <th><?php echo __('created_at'); ?></th>
                                <th><?php echo __('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notifications)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted"><?php echo __('no_notifications'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <tr>
                                        <td><?php echo $notification['icon']; ?></td>
                                        <td><?php echo sanitize($notification[$lang === 'ar' ? 'title_ar' : 'title_en']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $notification['type']; ?>">
                                                <?php echo $notification['type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $notification['target']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="delete_notification" value="1">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
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
    
    <!-- Add Notification Modal -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo __('add_notification'); ?></h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="add_notification" value="1">
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('title_en'); ?></label>
                    <input type="text" name="title_en" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('title_ar'); ?></label>
                    <input type="text" name="title_ar" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('content_en'); ?></label>
                    <textarea name="content_en" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('content_ar'); ?></label>
                    <textarea name="content_ar" class="form-textarea" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('type'); ?></label>
                    <select name="type" class="form-input">
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="success">Success</option>
                        <option value="error">Error</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('icon'); ?></label>
                    <input type="text" name="icon" class="form-input" value="📢" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label class="form-label"><?php echo __('target'); ?></label>
                    <select name="target" class="form-input">
                        <option value="all"><?php echo __('all'); ?></option>
                        <option value="users"><?php echo __('users'); ?></option>
                        <option value="teams"><?php echo __('teams'); ?></option>
                        <option value="admins"><?php echo __('admins'); ?></option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-neon btn-block"><?php echo __('save'); ?></button>
            </form>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        const modal = document.getElementById('addModal');
        
        function openModal() {
            modal.classList.add('active');
        }
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        // إغلاق المودال عند النقر خارجه
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // إغلاق المودال بمفتاح Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
