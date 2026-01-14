<?php
/**
 * XSS Lab 4 - Filter Bypass
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'xss_lab4_filter';
$folderName = 'xss/intermediate/lab4_filter';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$filtered = '';
$bypassed = false;
$advancedBypassed = false;

if ($page === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['message'] ?? '';
    // فلتر بسيط: حذف script tags
    $filtered = str_ireplace(['<script>', '</script>'], '', $input);
    if (preg_match('/onerror|onload|onclick|onmouseover|<img|<svg|<body|<iframe|javascript:/i', $filtered)) {
        $bypassed = true;
        $_SESSION['lab_' . $labKey . '_bypassed'] = true;
    }
    $message = $filtered;
}

if ($page === 'advanced') {
    if (!isset($_SESSION['lab_' . $labKey . '_bypassed'])) { 
        $page = 'contact'; 
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = $_POST['message'] ?? '';
        // فلتر متقدم
        $filtered = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $input);
        $filtered = preg_replace('/on\w+\s*=/i', '', $filtered);
        $filtered = preg_replace('/<(img|svg|body|iframe)/i', '&lt;$1', $filtered);
        
        // طرق تجاوز متقدمة
        if (preg_match('/javascript:|<details|<marquee|<object|<embed|<math|<audio|<video/i', $filtered) ||
            preg_match('/\bon\w+\s*=/i', $input)) {
            $advancedBypassed = true;
            $_SESSION['lab_' . $labKey . '_advanced'] = true;
        }
        $message = $filtered;
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_advanced'])) {
        $page = 'advanced';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_bypassed'], $_SESSION['lab_' . $labKey . '_advanced']);
    }
}

$GLOBALS['lab_title'] = 'XSS Filter Bypass';
renderLabHeader();
?>

<div class="lab-header">
    <h1>XSS Filter Bypass</h1>
    <p>تجاوز فلاتر الحماية</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>موقع <strong>شركة يمن تك</strong> للبرمجيات يدّعي أنه محمي ضد XSS.</p>
                <p>فريق التطوير أضاف فلتراً لحظر الـ script tags.</p>
                <p><strong>المهمة:</strong> أثبت أن الفلتر غير كافٍ لمنع XSS.</p>
            </div>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('contact'); ?>" class="btn btn-primary">صفحة التواصل</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'contact'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://yemen-tech.ye/contact</div></div>
        <div class="app-body">
            <h3>📧 تواصل معنا</h3>
            <div style="background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                ✅ هذا النموذج محمي ضد الهجمات الشائعة
            </div>
            <form method="POST" action="<?php echo stepUrl('contact'); ?>" class="app-form">
                <textarea name="message" placeholder="اكتب رسالتك..." rows="4"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                <button type="submit">إرسال</button>
            </form>
            <?php if ($message): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <strong>معاينة الرسالة:</strong>
                <div style="margin-top: 10px; padding: 10px; background: #fff; border-radius: 5px;"><?php echo $message; ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($bypassed): ?>
        <div class="alert alert-success">تجاوزت الفلتر الأساسي! لكن هناك فلتر آخر...</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('advanced'); ?>" class="btn btn-primary">النموذج المحدث</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'advanced'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://yemen-tech.ye/contact-v2</div></div>
        <div class="app-body">
            <h3>📧 نموذج التواصل v2.0</h3>
            <div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                ⚠️ تم تحديث نظام الحماية - الفلتر أقوى الآن
            </div>
            <form method="POST" action="<?php echo stepUrl('advanced'); ?>" class="app-form">
                <textarea name="message" placeholder="اكتب رسالتك..." rows="4"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                <button type="submit">إرسال</button>
            </form>
            <?php if ($message): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                <strong>معاينة:</strong>
                <div style="margin-top: 10px; padding: 10px; background: #fff; border-radius: 5px;"><?php echo $message; ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($advancedBypassed): ?>
        <div class="alert alert-success">ممتاز! تجاوزت الفلتر المتقدم أيضاً!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('contact'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color: #bbb; line-height: 2;">
            <li>الفلاتر القائمة على القوائم السوداء غالباً غير كافية</li>
            <li>هناك طرق متعددة لتنفيذ JavaScript في المتصفح</li>
            <li>الحماية الفعالة تتطلب نهجاً مختلفاً</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
