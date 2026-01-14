<?php
/**
 * Lab 3: Filter Bypass with Nested Traversal
 * المستوى: متوسط
 * ملف موحد
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'path_lab3';
$folderName = 'path_traversal/intermediate/lab3_nested';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$content = '';
$success = false;

if ($page === 'files') {
    $file = $_GET['f'] ?? '';
    
    if (!empty($file)) {
        $filtered = str_replace('../', '', $file);
        $filtered = str_replace('..\\', '', $filtered);
        
        if (strpos($filtered, '../') !== false || strpos($filtered, '..\\') !== false) {
            if (strpos($filtered, 'passwd') !== false) {
                $content = "root:x:0:0:root:/root:/bin/bash\nmysql:x:27:27:MySQL Server:/var/lib/mysql:/bin/false";
                $message = '🎉 نجحت في تجاوز الفلتر بالتداخل!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
            } else {
                $message = '⚠️ تجاوز ناجح! حاول الوصول لـ /etc/passwd';
            }
        } else {
            $message = '📄 تم فلترة المدخل. المسار النظيف: ' . htmlspecialchars($filtered);
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'files';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'Nested Path Traversal';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Nested Path Traversal</h1>
    <p>تجاوز الفلتر بالتداخل</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>نظام <strong>بوابة تعز</strong> يحذف <code>../</code> من المدخلات كحماية.</p>
                <p><strong>هدفك:</strong> استخدم تداخل المسارات لتجاوز الحذف.</p>
            </div>
        </div>
        <div style="background:#e3f2fd;padding:15px;border-radius:8px;margin:20px 0;">
            💡 الفلتر يحذف الأنماط الخطيرة مرة واحدة. فكر كيف يمكن استغلال هذا السلوك.
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('files'); ?>" class="btn btn-primary">عرض الملفات</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'files'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://taiz-portal.ye/files</div></div>
        <div class="app-body">
            <h3>📁 عرض الملفات</h3>
            
            <form method="GET" class="app-form" style="margin-bottom:20px;">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="files">
                <input type="text" name="f" placeholder="اسم الملف..." value="<?php echo htmlspecialchars($_GET['f'] ?? ''); ?>">
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
            <li>الفلترة التي تُنفذ مرة واحدة يمكن تجاوزها</li>
            <li>يجب استخدام فلترة متكررة حتى لا يتبقى أي نمط خطير</li>
            <li>تداخل الأنماط (Nested Patterns) تقنية شائعة للتجاوز</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
