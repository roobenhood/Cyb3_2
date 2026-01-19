<?php
/**
 * Lab 1: Basic Path Traversal
 * المستوى: مبتدئ
 * ملف موحد
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'path_lab1';
$folderName = 'path_traversal/beginner/lab1_basic';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$books = [
    'book1.txt' => 'كتاب تاريخ اليمن القديم - الفصل الأول: الحضارات اليمنية العريقة...',
    'book2.txt' => 'كتاب الطبخ اليمني - وصفة السلتة الصنعانية الأصلية...',
    'book3.txt' => 'كتاب الشعر اليمني - قصائد من التراث اليمني العريق...'
];

$message = '';
$content = '';
$success = false;

if ($page === 'view') {
    $file = $_GET['file'] ?? '';
    
    if (!empty($file)) {
        if (strpos($file, '../') !== false || strpos($file, '..\\') !== false) {
            if (strpos($file, 'etc/passwd') !== false) {
                $content = "root:x:0:0:root:/root:/bin/bash\nwww-data:x:33:33:www-data:/var/www:/usr/sbin/nologin\nadmin:x:1000:1000:Admin User:/home/admin:/bin/bash";
                $message = '🎉 نجحت في قراءة /etc/passwd!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
            } elseif (strpos($file, 'secrets') !== false || strpos($file, 'config') !== false) {
                $content = 'ملف سري للنظام - تم الوصول بنجاح!';
                $message = '🎉 نجحت في قراءة ملف سري!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
            } else {
                $message = '⚠️ تم اكتشاف محاولة Path Traversal! حاول الوصول لملف معروف.';
            }
        } elseif (isset($books[$file])) {
            $content = $books[$file];
            $message = '📚 محتوى الكتاب:';
        } else {
            $message = '❌ الكتاب غير موجود.';
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'view';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success']);
    }
}

$GLOBALS['lab_title'] = 'Basic Path Traversal';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Basic Path Traversal</h1>
    <p>اكتشف ثغرة اجتياز المسار الأساسية</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>أنت تختبر نظام عرض الكتب في <strong>مكتبة صنعاء الرقمية</strong>.</p>
                <p>النظام يستخدم معامل <code>file</code> لعرض محتوى الكتب من مجلد <code>/books/</code>.</p>
                <p><strong>هدفك:</strong> اقرأ ملف خارج مجلد الكتب باستخدام Path Traversal.</p>
            </div>
        </div>
        <div style="background:#1a1a2e;padding:15px;border-radius:8px;margin:20px 0;color:#0f0;font-family:monospace;">
            <strong>رابط عرض الكتاب:</strong><br>
            <code>?step=view&file=book1.txt</code>
        </div>
        <div style="background:#e3f2fd;padding:15px;border-radius:8px;margin:20px 0;">
            💡 لاحظ كيف يتم تحديد الملف المطلوب في الرابط. هل يمكنك الوصول لملفات خارج المجلد المحدد؟
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('view', ['file' => 'book1.txt']); ?>" class="btn btn-primary">عرض الكتب</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'view'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-library.ye/view?file=<?php echo urlencode($_GET['file'] ?? ''); ?></div></div>
        <div class="app-body">
            <h3>📚 الكتب المتاحة</h3>
            <div style="display:flex;gap:10px;margin-bottom:20px;">
                <?php foreach (array_keys($books) as $book): ?>
                    <a href="<?php echo stepUrl('view', ['file' => $book]); ?>" class="btn btn-outline" style="background:#f0f0f0;color:#333;padding:8px 15px;border-radius:5px;text-decoration:none;"><?php echo $book; ?></a>
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
            <li>Path Traversal يسمح بالوصول لملفات خارج المجلد المحدد</li>
            <li>استخدام ../ للصعود في هيكل المجلدات</li>
            <li>يجب التحقق من المسارات وتنظيفها قبل استخدامها</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
