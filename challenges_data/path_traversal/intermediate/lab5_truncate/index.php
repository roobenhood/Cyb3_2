<?php
/**
 * Lab 5: Path Truncation (Null Byte)
 * المستوى: متوسط
 * ملف موحد
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'path_lab5';
$folderName = 'path_traversal/intermediate/lab5_truncate';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$content = '';
$success = false;

if ($page === 'records') {
    $name = $_GET['name'] ?? '';
    
    if (!empty($name)) {
        $has_null = (strpos($name, '%00') !== false || strpos($name, "\0") !== false);
        $has_traversal = (strpos($name, '../') !== false || strpos(urldecode($name), '../') !== false);
        
        if ($has_null && $has_traversal) {
            $decoded = urldecode($name);
            $clean = str_replace(['%00', "\0"], '', $decoded);
            
            if (strpos($clean, 'passwd') !== false) {
                $content = "root:x:0:0:root:/root:/bin/bash\nnobody:x:65534:65534:nobody:/nonexistent:/usr/sbin/nologin";
                $message = '🎉 نجحت باستخدام Null Byte Truncation!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
            } else {
                $message = '⚠️ Null Byte يعمل! حاول الوصول لـ passwd';
            }
        } elseif ($has_traversal) {
            $message = '❌ Path Traversal مكتشف، لكن .pdf أُضيف للنهاية!';
        } else {
            $fullPath = $name . '.pdf';
            $message = '📋 محاولة قراءة: ' . htmlspecialchars($fullPath);
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'records';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'Null Byte Truncation';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Null Byte Truncation</h1>
    <p>اقتطاع الامتداد المُضاف</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>نظام <strong>مستشفى إب</strong> يضيف <code>.pdf</code> تلقائياً لاسم الملف.</p>
                <p><strong>هدفك:</strong> استخدم Null Byte أو طرق أخرى لاقتطاع الامتداد.</p>
            </div>
        </div>
        <div style="background:#1a1a2e;padding:15px;border-radius:8px;margin:20px 0;color:#0f0;font-family:monospace;">
            <strong>السلوك الحالي:</strong><br>
            <code>?step=records&name=patient1</code> → يقرأ <code>patient1.pdf</code>
        </div>
        <div style="background:#e3f2fd;padding:15px;border-radius:8px;margin:20px 0;">
            💡 ابحث عن تقنيات قطع السلسلة النصية في الأنظمة القديمة.
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('records'); ?>" class="btn btn-primary">عرض السجلات</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'records'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://ibb-hospital.ye/records</div></div>
        <div class="app-body">
            <h3>📋 سجلات المرضى</h3>
            
            <form method="GET" class="app-form" style="margin-bottom:20px;">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="records">
                <input type="text" name="name" placeholder="اسم السجل..." value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>">
                <button type="submit">عرض</button>
            </form>
            
            <?php if ($message): ?>
                <div style="background:<?php echo $success?'#e8f5e9':'#fff3cd';?>;padding:15px;border-radius:8px;margin-bottom:15px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($content): ?>
                <div style="background:#1a1a2e;padding:15px;border-radius:8px;color:#0f0;font-family:monospace;">
                    <pre style="margin:0;white-space:pre-wrap;"><?php echo htmlspecialchars($content); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($success || isset($_SESSION['lab_' . $labKey . '_success'])): ?>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>Null Byte (%00) يقطع النص في PHP القديم</li>
            <li>إضافة امتداد تلقائي يمكن تجاوزها</li>
            <li>تحديث PHP يحل هذه المشكلة</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
