<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
$nameCol = $lang === 'ar' ? 'name_ar' : 'name_en';

// جلب الـ Writeups المعتمدة
$stmt = $pdo->query("
    SELECT w.*, 
           u.username,
           c.$nameCol as challenge_name,
           cat.$nameCol as category_name
    FROM writeups w
    JOIN users u ON w.user_id = u.id
    JOIN challenges c ON w.challenge_id = c.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE w.status = 'approved'
    ORDER BY w.created_at DESC
    LIMIT 50
");
$writeups = $stmt->fetchAll();

$pageTitle = __('writeups');
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">📝 <span><?php echo __('writeups'); ?></span></h1>
        <p class="page-description"><?php echo __('writeups_description'); ?></p>
    </div>
    
    <?php if (isLoggedIn()): ?>
        <div class="text-right mb-4">
            <a href="submit_writeup.php" class="btn btn-neon">
                ✍️ <?php echo __('submit_writeup'); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="writeups-grid">
        <?php if (empty($writeups)): ?>
            <div class="empty-state">
                <div class="empty-icon">📄</div>
                <p><?php echo __('no_writeups'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($writeups as $writeup): ?>
                <div class="writeup-card">
                    <div class="writeup-header">
                        <span class="writeup-category"><?php echo sanitize($writeup['category_name']); ?></span>
                        <span class="writeup-challenge"><?php echo sanitize($writeup['challenge_name']); ?></span>
                    </div>
                    <h3 class="writeup-title">
                        <a href="view_writeup.php?id=<?php echo $writeup['id']; ?>">
                            <?php echo sanitize($writeup['title']); ?>
                        </a>
                    </h3>
                    <div class="writeup-meta">
                        <span class="writeup-author">
                            👤 <?php echo sanitize($writeup['username']); ?>
                        </span>
                        <span class="writeup-date">
                            📅 <?php echo date('Y-m-d', strtotime($writeup['created_at'])); ?>
                        </span>
                    </div>
                    <div class="writeup-stats">
                        <span>👁️ <?php echo $writeup['views_count']; ?></span>
                        <span>❤️ <?php echo $writeup['likes_count']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>