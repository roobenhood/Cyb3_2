<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
// ✅ إصلاح أمني: التحقق من اللغة
$lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';
$nameCol = $lang === 'ar' ? 'name_ar' : 'name_en';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    header('Location: writeups.php');
    exit;
}

// جلب الـ Writeup
$stmt = $pdo->prepare("
    SELECT w.*, 
           u.username,
           c.$nameCol as challenge_name,
           cat.$nameCol as category_name,
           c.difficulty
    FROM writeups w
    JOIN users u ON w.user_id = u.id
    JOIN challenges c ON w.challenge_id = c.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE w.id = ? AND w.status = 'approved'
");
$stmt->execute([$id]);
$writeup = $stmt->fetch();

if (!$writeup) {
    header('Location: writeups.php');
    exit;
}

// زيادة عداد المشاهدات
$stmt = $pdo->prepare("UPDATE writeups SET views_count = views_count + 1 WHERE id = ?");
$stmt->execute([$id]);

// التحقق من الإعجاب
$liked = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM writeup_likes WHERE writeup_id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $liked = (bool)$stmt->fetch();
}

$pageTitle = $writeup['title'];
include 'includes/header.php';
?>

<div class="container">
    <div class="writeup-view">
        <div class="writeup-view-header">
            <div class="writeup-breadcrumb">
                <a href="writeups.php"><?php echo __('writeups'); ?></a>
                <span>/</span>
                <span><?php echo sanitize($writeup['category_name']); ?></span>
                <span>/</span>
                <span><?php echo sanitize($writeup['challenge_name']); ?></span>
            </div>
            
            <h1 class="writeup-view-title"><?php echo sanitize($writeup['title']); ?></h1>
            
            <div class="writeup-view-meta">
                <span class="author">👤 <?php echo sanitize($writeup['username']); ?></span>
                <span class="date">📅 <?php echo date('Y-m-d H:i', strtotime($writeup['created_at'])); ?></span>
                <span class="views">👁️ <?php echo $writeup['views_count']; ?></span>
                <span class="likes">❤️ <?php echo $writeup['likes_count']; ?></span>
            </div>
            
            <div class="writeup-tags">
                <span class="tag tag-<?php echo $writeup['difficulty']; ?>">
                    <?php echo __($writeup['difficulty']); ?>
                </span>
            </div>
        </div>
        
        <div class="writeup-view-content card">
            <div class="markdown-content">
                <?php echo nl2br(sanitize($writeup['content'])); ?>
            </div>
        </div>
        
        <?php if (isLoggedIn()): ?>
            <div class="writeup-actions">
                <button class="btn <?php echo $liked ? 'btn-neon' : 'btn-outline'; ?>" 
                        onclick="likeWriteup(<?php echo $id; ?>)" id="likeBtn">
                    <?php echo $liked ? '❤️' : '🤍'; ?> 
                    <span id="likesCount"><?php echo $writeup['likes_count']; ?></span>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function likeWriteup(id) {
    fetch('api/like_writeup.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({writeup_id: id})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('likesCount').textContent = data.likes;
            const btn = document.getElementById('likeBtn');
            btn.classList.toggle('btn-neon');
            btn.classList.toggle('btn-outline');
            btn.innerHTML = (data.liked ? '❤️' : '🤍') + ' <span id="likesCount">' + data.likes + '</span>';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>