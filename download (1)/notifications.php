<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
$nameCol = $lang === 'ar' ? 'title_ar' : 'title_en';
$contentCol = $lang === 'ar' ? 'content_ar' : 'content_en';

// جلب الإشعارات النشطة
$stmt = $pdo->prepare("
    SELECT n.*, 
           $nameCol as title,
           $contentCol as content,
           (SELECT COUNT(*) FROM notification_reads nr WHERE nr.notification_id = n.id AND nr.user_id = ?) as is_read
    FROM notifications n
    WHERE n.is_active = 1 
    AND (n.target = 'all' OR n.target = 'users')
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([isLoggedIn() ? $_SESSION['user_id'] : 0]);
$notifications = $stmt->fetchAll();

// تحديد الإشعارات كمقروءة عند الزيارة
if (isLoggedIn()) {
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO notification_reads (notification_id, user_id) VALUES (?, ?)");
            $stmt->execute([$notification['id'], $_SESSION['user_id']]);
        }
    }
}

$pageTitle = __('notifications');
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">🔔 <span><?php echo __('notifications'); ?></span></h1>
        <p class="page-description"><?php echo __('notifications_description'); ?></p>
    </div>
    
    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p><?php echo __('no_notifications'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?php echo $notification['is_read'] ? 'read' : 'unread'; ?> notification-<?php echo $notification['type']; ?>">
                    <div class="notification-icon">
                        <?php echo $notification['icon']; ?>
                    </div>
                    <div class="notification-content">
                        <h3 class="notification-title"><?php echo sanitize($notification['title']); ?></h3>
                        <p class="notification-text"><?php echo nl2br(sanitize($notification['content'])); ?></p>
                        <span class="notification-time">
                            <?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>