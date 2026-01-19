<?php
/**
 * Lab 6: Absolute Path Injection
 * المستوى: متقدم
 * ملف موحد
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'path_lab6';
$folderName = 'path_traversal/advanced/lab6_absolute';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$content = '';
$success = false;

if ($page === 'download') {
    $file = $_GET['file'] ?? '';
    
    if (!empty($file)) {
        if (strpos($file, '../') !== false || strpos($file, '..\\') !== false) {
            $message = '❌ Path Traversal محظور!';
        } else {
            if (strpos($file, '/etc/passwd') === 0 || $file === '/etc/passwd') {
                $content = "root:x:0:0:root:/root:/bin/bash\nbank_admin:x:1000:1000:Bank Admin:/home/admin:/bin/bash";
                $message = '🎉 نجحت باستخدام المسار المطلق!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
            } elseif (strpos($file, '/') === 0) {
                $message = '⚠️ مسار مطلق مقبول! حاول /etc/passwd';
            } else {
                $message = '📁 ملف: ' . htmlspecialchars($file);
            }
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'download';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'Absolute Path Injection';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Absolute Path Injection</h1>
    <p>استخدام المسار المطلق مباشرة</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>نظام <strong>بنك صنعاء</strong> يفلتر <code>../</code> لكنه لا يتحقق من المسارات المطلقة.</p>
                <p><strong>هدفك:</strong> استخدم مساراً مطلقاً مباشرة.</p>
            </div>
        </div>
        <div style="background:#e3f2fd;padding:15px;border-radius:8px;margin:20px 0;">
            💡 الفلتر يحظر أنماط التنقل النسبي. هل هناك طريقة أخرى لتحديد مسار الملف؟
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('download'); ?>" class="btn btn-primary">تحميل الملفات</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'download'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-bank.ye/download</div></div>
        <div class="app-body">
            <h3>📁 تحميل الملفات</h3>
            
            <form method="GET" class="app-form" style="margin-bottom:20px;">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="download">
                <input type="text" name="file" placeholder="مسار الملف..." value="<?php echo htmlspecialchars($_GET['file'] ?? ''); ?>">
                <button type="submit">تحميل</button>
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
            <li>فلترة ../ وحدها غير كافية</li>
            <li>المسارات المطلقة (/etc/passwd) خطيرة أيضاً</li>
            <li>يجب استخدام whitelist للملفات المسموحة</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
