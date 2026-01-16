<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

// إحصائيات للصفحة الرئيسية
$stats = [];

// عدد التحديات
$stmt = $pdo->query("SELECT COUNT(*) FROM challenges WHERE is_active = 1");
$stats['challenges'] = $stmt->fetchColumn();

// عدد المستخدمين
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['users'] = $stmt->fetchColumn();

// عدد الفرق
$stmt = $pdo->query("SELECT COUNT(*) FROM teams");
$stats['teams'] = $stmt->fetchColumn();

// عدد الحلول
$stmt = $pdo->query("SELECT COUNT(*) FROM solves");
$stats['solves'] = $stmt->fetchColumn();

$pageTitle = 'الرئيسية';
include 'includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-terminal" style="margin-bottom: 30px;">
            <div class="terminal-box" style="display: inline-block; padding: 10px 20px;">
                <span class="terminal-prompt">root@alwani:~$</span>
                <span class="terminal-output"> ./start_hacking</span>
            </div>
        </div>
        
        <h1 class="hero-title">
            ALWANI<span>CTF</span>
        </h1>
        
        <h2 class="hero-subtitle">Capture The Flag</h2>
        
        <p class="hero-description">
            <?php echo __('hero_description'); ?>
        </p>
        
        <div class="hero-buttons">
            <a href="challenges.php" class="btn btn-neon btn-lg">🚩 ابدأ التحديات</a>
            <a href="scoreboard.php" class="btn btn-outline btn-lg">🏆 شاهد الترتيب</a>
        </div>
        
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['challenges']; ?>+</div>
                <div class="stat-label">تحدي</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['users']; ?>+</div>
                <div class="stat-label">هاكر</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $stats['teams']; ?>+</div>
                <div class="stat-label">فريق</div>
            </div>
        </div>
    </div>
</section>

<section style="padding: 80px 0;">
    <div class="container">
        <h2 class="page-title" style="text-align: center; margin-bottom: 50px;">
            <?php echo __('why_alwanictf'); ?>
        </h2>
        
        <div class="grid grid-3">
            <div class="card">
                <div style="font-size: 2rem; margin-bottom: 15px;">🚩</div>
                <h3 class="card-title" style="margin-bottom: 10px;">تحديات متنوعة</h3>
                <p style="color: var(--text-muted);">من استغلال الويب إلى التشفير، الهندسة العكسية والطب الشرعي الرقمي.</p>
            </div>
            
            <div class="card">
                <div style="font-size: 2rem; margin-bottom: 15px;">🏆</div>
                <h3 class="card-title" style="margin-bottom: 10px;">ترتيب مباشر</h3>
                <p style="color: var(--text-muted);">تابع تقدمك وتنافس مع أفضل الهاكرز حول العالم.</p>
            </div>
            
            <div class="card">
                <div style="font-size: 2rem; margin-bottom: 15px;">👥</div>
                <h3 class="card-title" style="margin-bottom: 10px;">منافسة الفرق</h3>
                <p style="color: var(--text-muted);">شكّل تحالفات مع زملائك واغزوا التحديات معاً.</p>
            </div>
            
            <div class="card">
                <div style="font-size: 2rem; margin-bottom: 15px;">🛡️</div>
                <h3 class="card-title" style="margin-bottom: 10px;">مستويات مختلفة</h3>
                <p style="color: var(--text-muted);">تحديات للجميع - من المبتدئين إلى المحترفين.</p>
            </div>
            
            <div class="card">
                <div style="font-size: 2rem; margin-bottom: 15px;">💻</div>
                <h3 class="card-title" style="margin-bottom: 10px;">فئات متعددة</h3>
                <p style="color: var(--text-muted);">Web, Pwn, Crypto, Forensics, Reverse وأكثر.</p>
            </div>
            
            <div class="card">
                <div style="font-size: 2rem; margin-bottom: 15px;">🔒</div>
                <h3 class="card-title" style="margin-bottom: 10px;">بيئة آمنة</h3>
                <p style="color: var(--text-muted);">تدرب على الاختراق الأخلاقي في بيئة معزولة وآمنة.</p>
            </div>
        </div>
    </div>
</section>

<section style="padding: 80px 0; text-align: center;">
    <div class="container">
        <h2 class="page-title" style="margin-bottom: 20px;">
            هل أنت مستعد <span>للتحدي</span>؟
        </h2>
        <p style="color: var(--text-muted); margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">
            انضم إلى آلاف الهاكرز حول العالم. ابدأ رحلتك اليوم وكن جزءاً من مجتمع الأمن السيبراني النخبوي.
        </p>
        
        <a href="challenges.php" class="btn btn-neon btn-lg animate-pulse">🚩 ابدأ التحديات الآن</a>
        
        <div class="terminal-box" style="max-width: 400px; margin: 40px auto 0; text-align: right;">
            <div class="terminal-line">
                <span class="terminal-prompt">$</span> whoami
            </div>
            <div class="terminal-line">
                <span class="terminal-output">future_hacker</span>
            </div>
            <div class="terminal-line">
                <span class="terminal-prompt">$</span> ./join_alwanictf.sh
            </div>
            <div class="terminal-line">
                <span class="terminal-output">Initializing hacker mode... █</span>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
