<?php
/**
 * SQLi Lab 3 - Blind SQL Injection
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'sqli_lab3_blind';
$folderName = 'sqli/intermediate/lab3_blind';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$patientId = $_GET['pid'] ?? '';
$result = null;
$blindDetected = false;
$timeDetected = false;

// Secret database name for extraction
$secretDbName = 'thawra_medical_db';

if ($page === 'check' && $patientId) {
    // Boolean-based blind
    if (preg_match("/(AND|OR)\s+[\d'\"]+\s*=\s*[\d'\"]+/i", $patientId) ||
        preg_match("/SUBSTRING|ASCII|ORD|MID|LEFT|RIGHT/i", $patientId)) {
        $blindDetected = true;
        $_SESSION['lab_' . $labKey . '_blind'] = true;
    }
    
    // Time-based blind
    if (preg_match("/SLEEP|BENCHMARK|WAITFOR|DELAY/i", $patientId)) {
        $timeDetected = true;
        $_SESSION['lab_' . $labKey . '_time'] = true;
    }
    
    // Determine result based on injection
    if (is_numeric($patientId)) {
        $result = ($patientId == '1234' || $patientId == '5678');
    } elseif (preg_match("/1=1|'1'='1|OR\s+1/i", $patientId)) {
        $result = true;
    } elseif (preg_match("/1=2|'1'='2|AND\s+0/i", $patientId)) {
        $result = false;
    } else {
        $result = false;
    }
}

$guess = $_POST['guess'] ?? '';
$correct = false;
if ($page === 'extract' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $normalizedGuess = strtolower(trim(str_replace(['_', '-', ' '], '', $guess)));
    $normalizedSecret = strtolower(str_replace(['_', '-', ' '], '', $secretDbName));
    
    if ($normalizedGuess === $normalizedSecret || 
        $normalizedGuess === 'thawramedicaldb' ||
        $guess === 'thawra_medical_db') {
        $correct = true;
        $_SESSION['lab_' . $labKey . '_solved'] = true;
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_solved'])) {
        $page = 'extract';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_blind'], $_SESSION['lab_' . $labKey . '_time'], $_SESSION['lab_' . $labKey . '_solved']);
    }
}

if ($page === 'extract' && !isset($_SESSION['lab_' . $labKey . '_blind']) && !isset($_SESSION['lab_' . $labKey . '_time'])) {
    $page = 'check';
}

$GLOBALS['lab_title'] = 'Blind SQLi';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Blind SQL Injection</h1>
    <p>استخراج البيانات بدون رؤية النتائج</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>نظام حجز المواعيد في <strong>مستشفى الثورة العام</strong> يتحقق من أرقام المرضى.</p>
                <p>لاحظت أن النظام لا يعرض رسائل خطأ مفصلة - فقط "موجود" أو "غير موجود".</p>
                <p><strong>الهدف:</strong> استخدم Blind SQLi لاستخراج اسم قاعدة البيانات.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة</h2>
            <p style="color:#aaa;">عندما لا ترى نتائج أو أخطاء مفصلة، قد تحتاج لاستخدام تقنيات أخرى لاستخراج البيانات.</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('check'); ?>" class="btn btn-primary">نظام التحقق من الموعد</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'check'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://thawra-hospital.ye/check-appointment?id=<?php echo urlencode($patientId); ?></div></div>
        <div class="app-body">
            <h3>🏥 مستشفى الثورة العام</h3>
            <p style="color:#666;margin-bottom:20px;">شارع الستين - صنعاء</p>
            
            <form method="GET" class="app-form">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="check">
                <label style="display:block;margin-bottom:10px;color:#666;">رقم ملف المريض:</label>
                <input type="text" name="pid" placeholder="أدخل رقم الملف" value="<?php echo htmlspecialchars($patientId); ?>">
                <button type="submit">التحقق من الموعد</button>
            </form>
            
            <?php if ($patientId !== ''): ?>
                <div style="margin-top:20px;padding:15px;border-radius:8px;background:<?php echo $result ? '#e8f5e9' : '#ffebee'; ?>;">
                    <?php if ($result): ?>
                        <span style="color:#2e7d32;">✓ يوجد موعد مسجل لهذا الرقم</span>
                    <?php else: ?>
                        <span style="color:#c62828;">✗ لا يوجد موعد مسجل</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($timeDetected): ?>
                    <div style="margin-top:10px;padding:10px;background:#fff3cd;border-radius:5px;font-size:0.9rem;color:#856404;">
                        ⏱️ الاستجابة تأخرت بشكل ملحوظ...
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($blindDetected || $timeDetected || isset($_SESSION['lab_' . $labKey . '_blind'])): ?>
        <div class="alert alert-success">اكتشفت ثغرة Blind SQLi!</div>
        <div class="lab-card">
            <p>الآن استخدم التقنية لاستخراج اسم قاعدة البيانات حرفاً بحرف.</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('extract'); ?>" class="btn btn-primary">مرحلة الاستخراج</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'extract'): ?>
    <div class="lab-card">
        <h2>استخراج اسم قاعدة البيانات</h2>
        <p style="color:#aaa;">استخدم تقنية Blind SQLi التي اكتشفتها لاستخراج اسم قاعدة البيانات.</p>
        
        <form method="POST" action="<?php echo stepUrl('extract'); ?>" class="lab-form" style="margin-top:20px;">
            <label style="color:#888;margin-bottom:10px;display:block;">ما اسم قاعدة البيانات التي استخرجتها؟</label>
            <input type="text" name="guess" placeholder="database_name" value="<?php echo htmlspecialchars($guess); ?>" style="margin-bottom:15px;">
            <button type="submit" class="btn btn-primary" style="width:100%;">تحقق من الإجابة</button>
        </form>
        
        <?php if ($guess && !$correct): ?>
            <div style="background:#ffebee;padding:10px;border-radius:5px;margin-top:15px;color:#c62828;">
                ❌ ليس هذا الاسم الصحيح. حاول مرة أخرى.
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($correct): ?>
        <?php renderSuccessBox($folderName); ?>
        <div class="lab-card">
            <h2>ما تعلمته</h2>
            <ul style="color:#bbb;margin-right:20px;line-height:2;">
                <li>Blind SQLi يُستخدم عندما لا تظهر نتائج أو أخطاء</li>
                <li>يتطلب صبراً وتقنيات مختلفة للاستخراج</li>
                <li>يمكن أتمتة العملية باستخدام أدوات</li>
            </ul>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('check'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
