<?php
/**
 * Lab 2: URL Encoded Path Traversal
 * المستوى: مبتدئ
 * ملف موحد
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'path_lab2';
$folderName = 'path_traversal/beginner/lab2_encoded';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$docs = [
    'treaty1.txt' => 'معاهدة عدن 1839 - الوثيقة التاريخية الأصلية...',
    'decree1.txt' => 'مرسوم تأسيس ميناء عدن الحر...'
];

$message = '';
$content = '';
$success = false;

if ($page === 'docs') {
    $file = $_GET['doc'] ?? '';
    
    if (!empty($file)) {
        if (strpos($file, '../') !== false) {
            $message = '❌ تم حظر محاولة Path Traversal! جرب طريقة أخرى.';
        } else {
            $decoded = urldecode($file);
            
            if (strpos($decoded, '../') !== false || strpos($decoded, '..\\') !== false) {
                if (strpos($decoded, 'passwd') !== false || strpos($decoded, 'shadow') !== false) {
                    $content = "root:x:0:0:root:/root:/bin/bash\nadmin:x:1000:1000::/home/admin:/bin/bash";
                    $message = '🎉 نجحت في تجاوز الفلتر بالترميز!';
                    $success = true;
                    $_SESSION['lab_' . $labKey . '_success'] = true;
                } elseif (strpos($decoded, 'config') !== false || strpos($decoded, 'secret') !== false) {
                    $content = "DATABASE_PASSWORD=Admin@2024\nAPI_KEY=sk_live_abc123";
                    $message = '🎉 وصلت لملف الإعدادات!';
                    $success = true;
                    $_SESSION['lab_' . $labKey . '_success'] = true;
                } else {
                    $message = '⚠️ تجاوز ناجح! حاول الوصول لملف معروف (passwd, config)';
                }
            } elseif (isset($docs[$file])) {
                $content = $docs[$file];
                $message = '📜 محتوى الوثيقة:';
            } else {
                $message = '❌ الوثيقة غير موجودة.';
            }
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'docs';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'URL Encoded Path Traversal';
renderLabHeader();
?>

<div class="lab-header">
    <h1>URL Encoded Path Traversal</h1>
    <p>تجاوز الفلتر باستخدام URL Encoding</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>أنت تختبر <strong>أرشيف عدن الإلكتروني</strong> للوثائق التاريخية.</p>
                <p>النظام يحظر استخدام <code>../</code> مباشرة كحماية بسيطة.</p>
                <p><strong>هدفك:</strong> تجاوز الفلتر باستخدام URL Encoding.</p>
            </div>
        </div>
        <div style="background:#e3f2fd;padding:15px;border-radius:8px;margin:20px 0;">
            💡 الفلتر يحظر الأنماط المباشرة. ابحث عن طرق ترميز بديلة للأحرف الخاصة.
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('docs'); ?>" class="btn btn-primary">عرض الوثائق</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'docs'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://aden-archive.ye/docs</div></div>
        <div class="app-body">
            <h3>📜 الوثائق المتاحة</h3>
            <div style="display:flex;gap:10px;margin-bottom:20px;">
                <?php foreach (array_keys($docs) as $doc): ?>
                    <a href="<?php echo stepUrl('docs', ['doc' => $doc]); ?>" class="btn btn-outline" style="background:#f0f0f0;color:#333;padding:8px 15px;border-radius:5px;text-decoration:none;"><?php echo $doc; ?></a>
                <?php endforeach; ?>
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
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>الفلاتر التي تبحث عن أنماط نصية محددة يمكن تجاوزها بالترميز</li>
            <li>يجب فك جميع أنواع الترميز قبل التحقق من المدخلات</li>
            <li>URL Encoding هو أحد أشكال التشفير الشائعة في الويب</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
