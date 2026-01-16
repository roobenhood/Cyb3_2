<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
// ✅ إصلاح أمني: التحقق من اللغة
$lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';

$t = loadLanguage($lang);
$name_col = 'name_' . $lang;
$desc_col = 'description_' . $lang;
$hint_col = 'hint_' . $lang;

// التحقق من وجود معرف التحدي
$challenge_id = intval($_GET['id'] ?? 0);

if ($challenge_id <= 0) {
    header('Location: challenges.php');
    exit;
}

// جلب بيانات التحدي
$stmt = $pdo->prepare("
    SELECT c.*, cat.name_{$lang} as category_name, cat.color as category_color
    FROM challenges c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.id = ? AND c.is_active = 1
");
$stmt->execute([$challenge_id]);
$challenge = $stmt->fetch();

if (!$challenge) {
    header('Location: challenges.php');
    exit;
}

$challenge_name = $challenge[$name_col];
$challenge_desc = $challenge[$desc_col];
$challenge_hint = $challenge[$hint_col];

// التحقق من حل التحدي
$isSolved = false;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM solves WHERE user_id = ? AND challenge_id = ?");
    $stmt->execute([$_SESSION['user_id'], $challenge_id]);
    $isSolved = $stmt->fetch() !== false;
}

// الحصول على عدد الحلول
$stmt = $pdo->prepare("SELECT COUNT(*) FROM solves WHERE challenge_id = ?");
$stmt->execute([$challenge_id]);
$solve_count = $stmt->fetchColumn();

$pageTitle = $challenge_name;
include 'includes/header.php';
?>

<div class="container">
    <div style="margin-bottom: 20px;">
        <a href="challenges.php" class="btn btn-outline btn-sm">
            ← <?php echo $t['back_to_challenges'] ?? 'العودة للتحديات'; ?>
        </a>
    </div>
    
    <div class="challenge-view-container" style="display: grid; grid-template-columns: 1fr 350px; gap: 30px;">
        <div>
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                    <h2 style="font-family: var(--font-display); font-size: 1.5rem; margin: 0;">
                        <?php if ($isSolved): ?>✓ <?php endif; ?>
                        <?php echo sanitize($challenge_name); ?>
                    </h2>
                    <span style="background: var(--neon-green); color: var(--bg-primary); padding: 5px 15px; border-radius: 20px; font-weight: bold;">
                        <?php echo $challenge['points']; ?> <?php echo $t['points'] ?? 'نقطة'; ?>
                    </span>
                </div>
                
                <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
                    <span class="badge" style="background: <?php echo $challenge['category_color']; ?>20; color: <?php echo $challenge['category_color']; ?>; border-color: <?php echo $challenge['category_color']; ?>50;">
                        <?php echo $challenge['category_name']; ?>
                    </span>
                    <span class="badge badge-<?php echo $challenge['difficulty']; ?>">
                        <?php echo $t[$challenge['difficulty']] ?? $challenge['difficulty']; ?>
                    </span>
                    <span class="badge" style="background: rgba(255,255,255,0.1);">
                        👥 <?php echo $solve_count; ?> <?php echo $t['solves'] ?? 'حلول'; ?>
                    </span>
                </div>
                
                <div style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.7;">
                    <?php echo nl2br(sanitize($challenge_desc)); ?>
                </div>
                
                <?php if (!empty($challenge_hint)): ?>
                    <details style="margin-bottom: 20px; background: var(--bg-secondary); padding: 15px; border-radius: 8px;">
                        <summary style="color: var(--neon-cyan); cursor: pointer; font-weight: 500;">
                            💡 <?php echo $t['hint'] ?? 'تلميح'; ?>
                            <?php if ($challenge['hint_cost'] > 0): ?>
                                <span style="color: var(--text-muted);">(−<?php echo $challenge['hint_cost']; ?> <?php echo $t['points'] ?? 'نقطة'; ?>)</span>
                            <?php endif; ?>
                        </summary>
                        <p style="color: var(--text-muted); margin-top: 10px;">
                            <?php echo sanitize($challenge_hint); ?>
                        </p>
                    </details>
                <?php endif; ?>
                
                <a href="challenge_play.php?id=<?php echo $challenge['id']; ?>" target="_blank" class="btn btn-neon" style="width: 100%; text-align: center; font-size: 1.1rem; padding: 15px;">
                    🎮 <?php echo $t['start_challenge'] ?? 'ابدأ التحدي'; ?> ↗
                </a>
            </div>
        </div>
        
        <div>
            <div class="card">
                <h3 style="margin-bottom: 15px; font-family: var(--font-display);">
                    🚩 <?php echo $t['submit_flag'] ?? 'إرسال الفلاج'; ?>
                </h3>
                
                <?php if ($isSolved): ?>
                    <div style="text-align: center; padding: 30px; background: rgba(0, 255, 136, 0.1); border-radius: 8px; border: 1px solid rgba(0, 255, 136, 0.3);">
                        <div style="font-size: 3rem; margin-bottom: 10px;">🎉</div>
                        <p style="color: var(--neon-green); font-weight: bold; font-size: 1.2rem;">
                            <?php echo $t['already_solved'] ?? 'لقد حللت هذا التحدي!'; ?>
                        </p>
                    </div>
                <?php elseif (isLoggedIn()): ?>
                    <form onsubmit="event.preventDefault(); submitFlag(<?php echo $challenge['id']; ?>);">
                        <div class="form-group">
                            <input 
                                type="text" 
                                id="flag-input-<?php echo $challenge['id']; ?>"
                                class="form-input" 
                                placeholder="CTF{your_flag_here}"
                                style="font-family: var(--font-mono);"
                            >
                        </div>
                        <button type="submit" class="btn btn-neon" style="width: 100%;">
                            🚩 <?php echo $t['submit'] ?? 'إرسال'; ?>
                        </button>
                    </form>
                    <div id="flag-result-<?php echo $challenge['id']; ?>" style="margin-top: 15px;"></div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; background: var(--bg-secondary); border-radius: 8px;">
                        <p style="color: var(--text-muted); margin-bottom: 15px;">
                            <?php echo $t['login_to_solve'] ?? 'يجب تسجيل الدخول لحل التحديات'; ?>
                        </p>
                        <a href="login.php" class="btn btn-neon"><?php echo $t['login'] ?? 'تسجيل الدخول'; ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media (max-width: 900px) {
    .challenge-view-container {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
