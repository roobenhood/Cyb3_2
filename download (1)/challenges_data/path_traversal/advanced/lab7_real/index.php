<?php
/**
 * Lab 7: Real World Path Traversal Attack
 * المستوى: متقدم
 * ملف موحد
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'path_lab7';
$folderName = 'path_traversal/advanced/lab7_real';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$content = '';
$success = false;

if ($page === 'portal') {
    $template = $_GET['tpl'] ?? '';
    
    if (!empty($template)) {
        $filtered = str_replace(['../', '..\\'], '', $template);
        $decoded = urldecode($filtered);
        
        if (strpos($decoded, '../') !== false) {
            $message = '❌ URL Encoding مكتشف!';
        } else {
            $double_decoded = urldecode($decoded);
            
            $target_patterns = ['database.yml', 'config/database', '/app/config'];
            $found = false;
            
            foreach ($target_patterns as $pattern) {
                if (strpos($double_decoded, $pattern) !== false || strpos($filtered, $pattern) !== false) {
                    $found = true;
                    break;
                }
            }
            
            $bypass_used = (
                strpos($template, '....//') !== false ||
                strpos($template, '%252e') !== false ||
                strpos($template, '/app/') === 0
            );
            
            if ($found && $bypass_used) {
                $content = "# Database Configuration\n";
                $content .= "production:\n";
                $content .= "  adapter: mysql2\n";
                $content .= "  host: db.mukalla-tech.ye\n";
                $content .= "  username: admin\n";
                $content .= "  password: M@k@ll@2024_Pr0d!";
                $message = '🎉 ممتاز! وصلت لملف قاعدة البيانات!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
            } elseif ($bypass_used) {
                $message = '⚠️ تقنية جيدة! حاول الوصول لـ /app/config/database.yml';
            } else {
                $message = '📄 قالب: ' . htmlspecialchars($filtered);
            }
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'portal';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'Real World Path Traversal';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Real World Path Traversal</h1>
    <p>اجمع كل التقنيات لهجوم حقيقي</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName, 'أكملت جميع لابات Path Traversal!'); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>المهمة</h2>
            <div class="scenario-box">
                <p>أنت مختبر اختراق تختبر بوابة <strong>شركة المكلا التقنية</strong>.</p>
                <p>النظام يستخدم حماية متعددة الطبقات ضد Path Traversal.</p>
                <p><strong>هدفك:</strong> اجمع كل ما تعلمته للوصول لملف <code>/app/config/database.yml</code></p>
            </div>
        </div>
        <div style="background:#e3f2fd;padding:15px;border-radius:8px;margin:20px 0;">
            💡 استخدم كل ما تعلمته في اللابات السابقة. الحماية متعددة الطبقات.
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('portal'); ?>" class="btn btn-primary">دخول البوابة</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'portal'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://mukalla-tech.ye/templates</div></div>
        <div class="app-body">
            <h3>🏢 شركة المكلا التقنية</h3>
            <p style="color:#888;margin-bottom:20px;">اختر قالب العرض:</p>
            <div style="display:flex;gap:10px;margin-bottom:20px;">
                <a href="<?php echo stepUrl('portal', ['tpl' => 'home.html']); ?>" class="btn btn-outline" style="background:#f0f0f0;color:#333;padding:8px 15px;border-radius:5px;text-decoration:none;">الرئيسية</a>
                <a href="<?php echo stepUrl('portal', ['tpl' => 'about.html']); ?>" class="btn btn-outline" style="background:#f0f0f0;color:#333;padding:8px 15px;border-radius:5px;text-decoration:none;">من نحن</a>
                <a href="<?php echo stepUrl('portal', ['tpl' => 'contact.html']); ?>" class="btn btn-outline" style="background:#f0f0f0;color:#333;padding:8px 15px;border-radius:5px;text-decoration:none;">اتصل بنا</a>
            </div>
            
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
    <?php renderSuccessBox($folderName, 'أكملت جميع لابات Path Traversal!'); ?>
    <div class="lab-card">
        <h2>ملخص Path Traversal</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>تعلمت تقنيات متعددة لتجاوز فلاتر المسارات</li>
            <li>كل طبقة حماية لها نقاط ضعف محتملة</li>
            <li>الحماية الفعالة تتطلب عدة طبقات مجتمعة</li>
        </ul>
    </div>
    <div style="background:#1a1a2e;padding:15px;border-radius:8px;margin:20px 0;color:#0f0;font-family:monospace;">
        <h4 style="color:#fff;">الحماية الصحيحة:</h4>
        <pre style="margin:0;">
$base = '/var/www/templates/';
$file = basename($_GET['tpl']);
$path = realpath($base . $file);
if ($path && strpos($path, $base) === 0) {
    include $path;
}
        </pre>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
