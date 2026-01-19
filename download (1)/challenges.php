<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
// ✅ إصلاح أمني: التحقق من اللغة لمنع SQL Injection
$lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';

$t = loadLanguage($lang);
$name_col = 'name_' . $lang;
$desc_col = 'description_' . $lang;
$hint_col = 'hint_' . $lang;

// جلب الفئات - ✅ استخدام prepared statement
$stmt = $pdo->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name_" . $lang);
$stmt->execute();
$categories = $stmt->fetchAll();

// جلب التحديات النشطة
$stmt = $pdo->query("
    SELECT c.*, cat.name_{$lang} as category_name, cat.name_en as category_slug, cat.color as category_color,
           (SELECT COUNT(*) FROM solves WHERE challenge_id = c.id) as solve_count
    FROM challenges c
    JOIN categories cat ON c.category_id = cat.id
    WHERE c.is_active = 1
    ORDER BY c.category_id, c.points
");
$challenges = $stmt->fetchAll();

// جلب التحديات المحلولة للمستخدم
$solved_challenges = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT challenge_id FROM solves WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $solved_challenges = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = $t['challenges'] ?? 'التحديات';
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header page-header--center-desc">
        <h1 class="page-title">🚩 <span><?php echo $t['challenges'] ?? 'التحديات'; ?></span></h1>
        <p class="page-description"><?php echo $t['challenges_description'] ?? 'اختبر مهاراتك عبر فئات متعددة. اجمع الفلاجات واكسب النقاط.'; ?></p>
    </div>
    
    <div style="margin-bottom: 30px;">
        <input 
            type="text" 
            id="searchInput" 
            class="form-input" 
            placeholder="🔍 <?php echo $t['search_challenge'] ?? 'ابحث عن تحدي...'; ?>"
            onkeyup="searchChallenges()"
            style="max-width: 400px; margin-bottom: 20px;"
        >
        
        <div class="filters">
            <button class="filter-btn category-btn active" onclick="filterChallenges('all')"><?php echo $t['all'] ?? 'الكل'; ?></button>
            <?php foreach ($categories as $cat): ?>
                <?php $cat_name = $cat[$name_col]; ?>
                <button class="filter-btn category-btn" onclick="filterChallenges('<?php echo strtolower($cat['name_en']); ?>')">
                    <?php echo $cat_name; ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="filters" style="margin-top: 10px;">
            <span style="color: var(--text-muted); margin-left: 10px;"><?php echo $t['difficulty'] ?? 'الصعوبة'; ?>:</span>
            <button class="filter-btn difficulty-btn active" onclick="filterByDifficulty('all')"><?php echo $t['all'] ?? 'الكل'; ?></button>
            <button class="filter-btn difficulty-btn" onclick="filterByDifficulty('easy')"><?php echo $t['easy'] ?? 'سهل'; ?></button>
            <button class="filter-btn difficulty-btn" onclick="filterByDifficulty('medium')"><?php echo $t['medium'] ?? 'متوسط'; ?></button>
            <button class="filter-btn difficulty-btn" onclick="filterByDifficulty('hard')"><?php echo $t['hard'] ?? 'صعب'; ?></button>
            <button class="filter-btn difficulty-btn" onclick="filterByDifficulty('insane')"><?php echo $t['insane'] ?? 'جنوني'; ?></button>
        </div>
    </div>
    
    <div class="grid grid-3">
        <?php foreach ($challenges as $challenge): ?>
            <?php 
            $isSolved = in_array($challenge['id'], $solved_challenges);
            $challenge_name = $challenge[$name_col];
            $challenge_desc = $challenge[$desc_col];
            $challenge_hint = $challenge[$hint_col];
            ?>
            <div class="card challenge-card <?php echo $isSolved ? 'solved' : ''; ?>" 
                 data-category="<?php echo strtolower($challenge['category_slug']); ?>"
                 data-difficulty="<?php echo $challenge['difficulty']; ?>">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php if ($isSolved): ?>✓ <?php endif; ?>
                        <?php echo sanitize($challenge_name); ?>
                    </h3>
                    <span class="card-points"><?php echo $challenge['points']; ?></span>
                </div>
                
                <div style="display: flex; gap: 8px; margin-bottom: 15px;">
                    <span class="badge" style="background: <?php echo $challenge['category_color']; ?>20; color: <?php echo $challenge['category_color']; ?>; border-color: <?php echo $challenge['category_color']; ?>50;">
                        <?php echo $challenge['category_name']; ?>
                    </span>
                    <span class="badge badge-<?php echo $challenge['difficulty']; ?>">
                        <?php echo $t[$challenge['difficulty']] ?? $challenge['difficulty']; ?>
                    </span>
                </div>
                
                <p style="color: var(--text-muted); margin-bottom: 15px; font-size: 0.9rem;">
                    <?php echo nl2br(sanitize(mb_substr($challenge_desc, 0, 100))); ?>...
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: var(--text-muted); font-size: 0.85rem;">
                        <?php echo $challenge['solve_count']; ?> <?php echo $t['solves'] ?? 'حلول'; ?>
                    </span>
                    
                    <?php if (!$isSolved): ?>
                        <a href="challenge_view.php?id=<?php echo $challenge['id']; ?>" class="btn btn-sm btn-neon">
                            🚩 <?php echo $t['solve'] ?? 'حل'; ?>
                        </a>
                    <?php else: ?>
                        <a href="challenge_view.php?id=<?php echo $challenge['id']; ?>" style="color: var(--neon-green); font-size: 0.85rem;">✓ <?php echo $t['solved'] ?? 'محلول'; ?></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="modal-overlay" id="modal-<?php echo $challenge['id']; ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title"><?php echo sanitize($challenge_name); ?></h3>
                        <button class="modal-close" onclick="closeModal('modal-<?php echo $challenge['id']; ?>')">&times;</button>
                    </div>
                    
                    <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                        <span class="badge" style="background: <?php echo $challenge['category_color']; ?>20; color: <?php echo $challenge['category_color']; ?>; border-color: <?php echo $challenge['category_color']; ?>50;">
                            <?php echo $challenge['category_name']; ?>
                        </span>
                        <span class="badge badge-<?php echo $challenge['difficulty']; ?>">
                            <?php echo $t[$challenge['difficulty']] ?? $challenge['difficulty']; ?>
                        </span>
                        <span class="badge" style="background: rgba(0, 255, 136, 0.1); color: var(--neon-green); border-color: rgba(0, 255, 136, 0.3);">
                            <?php echo $challenge['points']; ?> <?php echo $t['points'] ?? 'نقطة'; ?>
                        </span>
                    </div>
                    
                    <div style="color: var(--text-muted); margin-bottom: 20px; white-space: pre-line;">
                        <?php echo nl2br(sanitize($challenge_desc)); ?>
                    </div>
                    
                    <?php if (!empty($challenge_hint)): ?>
                        <details style="margin-bottom: 20px;">
                            <summary style="color: var(--neon-cyan); cursor: pointer;">💡 <?php echo $t['hint'] ?? 'تلميح'; ?> <?php if ($challenge['hint_cost'] > 0): ?>(<?php echo $challenge['hint_cost']; ?> <?php echo $t['points'] ?? 'نقطة'; ?>)<?php endif; ?></summary>
                            <p style="color: var(--text-muted); margin-top: 10px; padding: 10px; background: var(--bg-secondary); border-radius: 8px;">
                                <?php echo sanitize($challenge_hint); ?>
                            </p>
                        </details>
                    <?php endif; ?>
                    
                    <?php 
                    $files = getChallengeFiles($challenge['folder_name']);
                    if (!empty($files)): 
                    ?>
                        <div style="margin-bottom: 20px;">
                            <p style="color: var(--text-muted); margin-bottom: 10px;">📁 <?php echo $t['files'] ?? 'الملفات'; ?>:</p>
                            <?php foreach ($files as $file): ?>
                                <a href="download.php?challenge=<?php echo urlencode($challenge['folder_name']); ?>&file=<?php echo urlencode($file['name']); ?>" class="btn btn-outline btn-sm" style="margin-left: 5px; margin-bottom: 5px;">
                                    <?php echo sanitize($file['name']); ?> (<?php echo formatFileSize($file['size']); ?>)
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isLoggedIn()): ?>
                        <form onsubmit="event.preventDefault(); submitFlag(<?php echo $challenge['id']; ?>);">
                            <div class="form-group">
                                <label class="form-label"><?php echo $t['enter_flag'] ?? 'أدخل الفلاج'; ?></label>
                                <input 
                                    type="text" 
                                    id="flag-input-<?php echo $challenge['id']; ?>"
                                    class="form-input" 
                                    placeholder="CTF{your_flag_here}"
                                >
                            </div>
                            <button type="submit" class="btn btn-neon" style="width: 100%;">
                                🚩 <?php echo $t['submit_flag'] ?? 'إرسال الفلاج'; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; background: var(--bg-secondary); border-radius: 8px;">
                            <p style="color: var(--text-muted); margin-bottom: 15px;"><?php echo $t['login_to_solve'] ?? 'يجب تسجيل الدخول لحل التحديات'; ?></p>
                            <a href="login.php" class="btn btn-neon"><?php echo $t['login'] ?? 'تسجيل الدخول'; ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($challenges)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🔒</div>
            <h3 style="color: var(--text-muted);"><?php echo $t['no_challenges'] ?? 'لا توجد تحديات حالياً'; ?></h3>
            <p style="color: var(--text-muted);"><?php echo $t['stay_tuned'] ?? 'ترقب إضافة تحديات جديدة قريباً!'; ?></p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
