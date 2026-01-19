<?php
require_once 'config.php';
requireLogin();

$lang = getCurrentLanguage();
$nameCol = $lang === 'ar' ? 'name_ar' : 'name_en';
$user_id = $_SESSION['user_id'];

// جلب التحديات التي حلها المستخدم ولم يكتب لها writeup
$stmt = $pdo->prepare("
    SELECT c.id, c.$nameCol as name
    FROM challenges c
    JOIN solves s ON c.id = s.challenge_id
    LEFT JOIN writeups w ON c.id = w.challenge_id AND w.user_id = ?
    WHERE s.user_id = ? AND w.id IS NULL AND c.is_active = 1
    ORDER BY s.solved_at DESC
");
$stmt->execute([$user_id, $user_id]);
$solved_challenges = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $challenge_id = intval($_POST['challenge_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    
    if (!$challenge_id || empty($title) || empty($content)) {
        $error = __('fill_all_fields');
    } else {
        // التحقق أن المستخدم حل التحدي
        $stmt = $pdo->prepare("SELECT id FROM solves WHERE user_id = ? AND challenge_id = ?");
        $stmt->execute([$user_id, $challenge_id]);
        if (!$stmt->fetch()) {
            $error = __('must_solve_first');
        } else {
            // التحقق أنه لم يكتب writeup مسبقاً
            $stmt = $pdo->prepare("SELECT id FROM writeups WHERE user_id = ? AND challenge_id = ?");
            $stmt->execute([$user_id, $challenge_id]);
            if ($stmt->fetch()) {
                $error = __('writeup_exists');
            } else {
                // إدخال الـ Writeup
                $stmt = $pdo->prepare("INSERT INTO writeups (user_id, challenge_id, title, content) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $challenge_id, $title, $content]);
                
                logActivity('submit_writeup', "Submitted writeup for challenge ID: $challenge_id");
                flashMessage('success', __('writeup_submitted'));
                header('Location: writeups.php');
                exit;
            }
        }
    }
}

$pageTitle = __('submit_writeup');
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">✍️ <span><?php echo __('submit_writeup'); ?></span></h1>
        <p class="page-description"><?php echo __('submit_writeup_description'); ?></p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (empty($solved_challenges)): ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-icon">🔒</div>
                <p><?php echo __('no_solved_challenges'); ?></p>
                <a href="challenges.php" class="btn btn-neon mt-3"><?php echo __('view_challenges'); ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <form method="POST" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label" for="challenge_id"><?php echo __('challenge'); ?></label>
                    <select name="challenge_id" id="challenge_id" class="form-input" required>
                        <option value=""><?php echo __('select_challenge'); ?></option>
                        <?php foreach ($solved_challenges as $challenge): ?>
                            <option value="<?php echo $challenge['id']; ?>">
                                <?php echo sanitize($challenge['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="title"><?php echo __('writeup_title'); ?></label>
                    <input type="text" name="title" id="title" class="form-input" required maxlength="200" 
                           placeholder="<?php echo __('writeup_title_placeholder'); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="content"><?php echo __('writeup_content'); ?></label>
                    <textarea name="content" id="content" class="form-input form-textarea" rows="15" required 
                              placeholder="<?php echo __('writeup_content_placeholder'); ?>"></textarea>
                    <small class="form-hint"><?php echo __('markdown_supported'); ?></small>
                </div>
                
                <button type="submit" class="btn btn-neon btn-block">
                    📤 <?php echo __('submit'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>