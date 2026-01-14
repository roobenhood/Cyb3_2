<?php
/**
 * Lab 7: Multi-Layer Bypass
 * المستوى: متقدم
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'fileupload_lab7';
$folderName = 'fileupload/advanced/lab7_real';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$success = false;
$checks = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'upload' && isset($_FILES['doc'])) {
    $file = $_FILES['doc'];
    $content = file_get_contents($file['tmp_name']);
    $filename = $file['name'];
    $mime = $file['type'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // Check 1: Extension
    $alt_php_exts = ['phtml', 'php5', 'phar', 'php3', 'php4', 'phps'];
    $checks['extension'] = in_array($ext, $alt_php_exts);
    
    // Check 2: MIME Type spoofing
    $image_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    $checks['mime'] = in_array($mime, $image_mimes);
    
    // Check 3: Magic Bytes
    $has_magic = (strpos($content, 'GIF89a') === 0 || 
                  strpos($content, 'GIF87a') === 0 ||
                  strpos($content, "\xFF\xD8\xFF") === 0 ||
                  strpos($content, "\x89PNG") === 0);
    $checks['magic'] = $has_magic;
    
    // Check 4: PHP Code presence
    $checks['php_code'] = (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false);
    
    if ($checks['extension'] && $checks['mime'] && $checks['magic'] && $checks['php_code']) {
        $message = 'تم تجاوز جميع طبقات الحماية! RCE achieved.';
        $success = true;
        $_SESSION['lab_' . $labKey . '_success'] = true;
    } else {
        $failed = [];
        if (!$checks['extension']) $failed[] = 'Extension blocked';
        if (!$checks['mime']) $failed[] = 'MIME check failed';
        if (!$checks['magic']) $failed[] = 'Magic bytes invalid';
        if (!$checks['php_code']) $failed[] = 'No executable code';
        $message = 'فشل: ' . implode(' | ', $failed);
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'upload';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'Multi-Layer Bypass';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Multi-Layer Security Bypass</h1>
    <p>التحدي النهائي - تجاوز حماية متعددة الطبقات</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName, 'أكملت جميع تحديات File Upload!'); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>تقرير الاختبار النهائي</h2>
            <div class="scenario-box">
                <p><strong>العميل:</strong> بنك الاستثمار الوطني</p>
                <p><strong>النطاق:</strong> investment-bank.com/secure-upload</p>
                <p><strong>الوصف:</strong> نظام رفع مستندات العملاء مع حماية متقدمة.</p>
                <p><strong>المهمة:</strong> اختبار شامل لجميع آليات الحماية مجتمعة.</p>
            </div>
        </div>
        <div class="lab-card">
            <h3>طبقات الحماية المُعلنة</h3>
            <table style="width:100%;color:#aaa;font-size:13px;">
                <tr style="border-bottom:1px solid #333;">
                    <td style="padding:10px;">Layer 1</td>
                    <td style="padding:10px;">Extension Whitelist</td>
                    <td style="padding:10px;color:#f44336;">✗ PHP blocked</td>
                </tr>
                <tr style="border-bottom:1px solid #333;">
                    <td style="padding:10px;">Layer 2</td>
                    <td style="padding:10px;">MIME Type Validation</td>
                    <td style="padding:10px;color:#ff9800;">⚠ Images only</td>
                </tr>
                <tr style="border-bottom:1px solid #333;">
                    <td style="padding:10px;">Layer 3</td>
                    <td style="padding:10px;">Content Inspection</td>
                    <td style="padding:10px;color:#ff9800;">⚠ Magic bytes check</td>
                </tr>
                <tr>
                    <td style="padding:10px;">Layer 4</td>
                    <td style="padding:10px;">Code Detection</td>
                    <td style="padding:10px;color:#4caf50;">✓ Scans for scripts</td>
                </tr>
            </table>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('upload'); ?>" class="btn btn-primary">بدء الاختبار</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'upload'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://investment-bank.com/secure-upload</div></div>
        <div class="app-body">
            <h3>رفع مستند آمن</h3>
            
            <?php if ($message): ?>
                <div style="background:<?php echo $success?'#1a3a1a':'#2a2a2a';?>;padding:15px;border-radius:8px;margin-bottom:15px;border-right:3px solid <?php echo $success?'#4caf50':'#666';?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($checks)): ?>
                <div style="background:#1a1a2e;padding:15px;border-radius:8px;margin-bottom:15px;">
                    <div style="font-size:12px;color:#888;margin-bottom:10px;">نتائج الفحص:</div>
                    <?php foreach ($checks as $check => $passed): ?>
                        <div style="padding:5px 0;color:<?php echo $passed ? '#4caf50' : '#f44336'; ?>;">
                            <?php echo $passed ? '✓' : '✗'; ?> <?php echo ucfirst(str_replace('_', ' ', $check)); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success || isset($_SESSION['lab_' . $labKey . '_success'])): ?>
                <div class="text-center mt-20">
                    <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
                </div>
            <?php else: ?>
                <form method="POST" action="<?php echo stepUrl('upload'); ?>" enctype="multipart/form-data" class="app-form">
                    <label style="display:block;margin-bottom:10px;color:#888;">المستند:</label>
                    <input type="file" name="doc" required style="margin-bottom:15px;">
                    <button type="submit">رفع</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName, 'أكملت جميع تحديات File Upload!'); ?>
    <div class="lab-card">
        <h2>ملخص File Upload Attacks</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>تعلمت تقنيات متعددة لتجاوز فحوصات رفع الملفات</li>
            <li>كل طبقة حماية لها نقاط ضعف محتملة</li>
            <li>الحماية الفعالة تتطلب دمج عدة طبقات</li>
        </ul>
    </div>
    <div class="lab-card">
        <h3>Defense in Depth</h3>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>Whitelist extensions + MIME + Content validation</li>
            <li>Rename files with random names</li>
            <li>Store outside web root</li>
            <li>Disable script execution in upload directory</li>
            <li>Use dedicated storage services (S3, etc.)</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
