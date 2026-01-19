<?php
/**
 * Lab 4: Double URL Encoding
 * المستوى: متوسط
 * ملف موحد
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'path_lab4';
$folderName = 'path_traversal/intermediate/lab4_double';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$content = '';
$success = false;

if ($page === 'reports') {
    $report = $_GET['r'] ?? '';
    
    if (!empty($report)) {
        $decoded = urldecode($report);
        
        if (strpos($decoded, '../') !== false || strpos($decoded, '%2e') !== false) {
            $message = '❌ تم اكتشاف محاولة Path Traversal!';
        } else {
            $double_decoded = urldecode($decoded);
            
            if (strpos($double_decoded, '../') !== false) {
                if (strpos($double_decoded, 'passwd') !== false) {
                    $content = "root:x:0:0:root:/root:/bin/bash\napache:x:48:48:Apache:/usr/share/httpd:/sbin/nologin";
                    $message = '🎉 نجحت باستخدام Double Encoding!';
                    $success = true;
                    $_SESSION['lab_' . $labKey . '_success'] = true;
                } else {
                    $message = '⚠️ تم التجاوز! حاول الوصول لـ passwd';
                }
            } else {
                $message = '📊 تقرير غير موجود: ' . htmlspecialchars($decoded);
            }
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'reports';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'Double URL Encoding';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Double URL Encoding</h1>
    <p>تجاوز الفلتر بالترميز المزدوج</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>نظام <strong>الحديدة للتقارير</strong> يفك الترميز ويفحص المسارات.</p>
                <p><strong>هدفك:</strong> استخدم Double Encoding لتجاوز الفحص.</p>
            </div>
        </div>
        <div style="background:#e3f2fd;padding:15px;border-radius:8px;margin:20px 0;">
            💡 النظام يفك الترميز ويفحص المحتوى. هل هناك طريقة لتجاوز هذا الفحص؟
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('reports'); ?>" class="btn btn-primary">عرض التقارير</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'reports'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://hodeidah-reports.ye/view</div></div>
        <div class="app-body">
            <h3>📊 عرض التقارير</h3>
            
            <form method="GET" class="app-form" style="margin-bottom:20px;">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="reports">
                <input type="text" name="r" placeholder="اسم التقرير..." value="<?php echo htmlspecialchars($_GET['r'] ?? ''); ?>" style="width:350px;">
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
            <li>بعض التطبيقات تفك الترميز على مراحل متعددة</li>
            <li>الفحص الأمني قد يحدث قبل اكتمال جميع مراحل فك الترميز</li>
            <li>يجب التحقق من المدخلات بعد كل عملية معالجة</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
