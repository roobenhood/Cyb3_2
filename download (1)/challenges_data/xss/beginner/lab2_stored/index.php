<?php
/**
 * XSS Lab 2 - Stored XSS
 * المستوى: مبتدئ
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'xss_lab2_stored';
$folderName = 'xss/beginner/lab2_stored';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

if (!isset($_SESSION['lab_' . $labKey . '_comments'])) {
    $_SESSION['lab_' . $labKey . '_comments'] = [
        ['user' => 'م. أحمد الصنعاني', 'text' => 'هل جرب أحد توزيعة Kali الجديدة؟ أداء ممتاز!', 'time' => 'منذ ساعة', 'avatar' => '👨‍💻'],
        ['user' => 'سارة التقنية', 'text' => 'أبحث عن مصادر لتعلم اختبار الاختراق، أي اقتراحات؟', 'time' => 'منذ 45 دقيقة', 'avatar' => '👩‍💻'],
        ['user' => 'علي ناصر', 'text' => 'المنتدى رائع! شكراً للإدارة.', 'time' => 'منذ 30 دقيقة', 'avatar' => '🧑‍💻'],
    ];
}

$commentAdded = false;
if ($page === 'forum' && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['comment'])) {
    $comment = $_POST['comment'];
    if (preg_match('/<script|<img|<svg|onerror|onload/i', $comment)) {
        $_SESSION['lab_' . $labKey . '_xss_stored'] = true;
    }
    // إضافة التعليق في أعلى القائمة (الأحدث أولاً) لظهوره مباشرةً بدون تمرير
    array_unshift($_SESSION['lab_' . $labKey . '_comments'], [
        'user' => 'زائر جديد',
        'text' => $comment,
        'time' => 'الآن',
        'avatar' => '👤'
    ]);
    $commentAdded = true;
}

$xssStored = isset($_SESSION['lab_' . $labKey . '_xss_stored']);

if ($page === 'victim' && !$xssStored) {
    $page = 'forum';
}

$hasXSS = false;
if ($page === 'victim') {
    $comments = $_SESSION['lab_' . $labKey . '_comments'] ?? [];
    foreach ($comments as $comment) {
        if (preg_match('/alert\s*\(|confirm\s*\(|prompt\s*\(/i', $comment['text'])) {
            $hasXSS = true;
            $_SESSION['lab_' . $labKey . '_victim_hit'] = true;
            break;
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_victim_hit'])) {
        $page = 'victim';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_comments'], $_SESSION['lab_' . $labKey . '_xss_stored'], $_SESSION['lab_' . $labKey . '_victim_hit']);
    }
}

$GLOBALS['lab_title'] = 'Stored XSS';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Stored XSS</h1>
    <p>اختبار أمان منتدى تقني</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>أنت تختبر أمان <strong>منتدى صنعاء التقني</strong>، أكبر منتدى للمبرمجين في اليمن.</p>
                <p>المنتدى يسمح للأعضاء بنشر تعليقات ومناقشات.</p>
                <p><strong>الهدف:</strong> تحقق ما إذا كان يمكن تخزين كود خبيث يُنفذ على متصفحات الزوار الآخرين.</p>
            </div>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('forum'); ?>" class="btn btn-primary">دخول المنتدى</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'forum'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-tech-forum.ye/discussions</div></div>
        <div class="app-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h3 style="margin: 0;">💬 قسم النقاشات العامة</h3>
                <span style="background:#e3f2fd;padding:5px 10px;border-radius:15px;font-size:0.85rem;">👥 <?php echo count($_SESSION['lab_' . $labKey . '_comments']); ?> مشاركة</span>
            </div>
            
            <div style="max-height: 350px; overflow-y: auto; margin: 15px 0;">
                <?php foreach ($_SESSION['lab_' . $labKey . '_comments'] as $comment): ?>
                    <div style="background: #fff; border: 1px solid #e0e0e0; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <span style="font-size:1.5rem;"><?php echo $comment['avatar']; ?></span>
                            <div>
                                <strong style="color: #667eea;"><?php echo htmlspecialchars($comment['user']); ?></strong>
                                <span style="color: #999; font-size: 0.85rem; margin-right: 10px;"><?php echo $comment['time']; ?></span>
                            </div>
                        </div>
                        <p style="margin: 0;padding-right:40px;"><?php echo $comment['text']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" action="<?php echo stepUrl('forum'); ?>" class="app-form" style="border-top:1px solid #eee;padding-top:15px;">
                <textarea name="comment" placeholder="شارك رأيك أو سؤالك..." rows="3"></textarea>
                <button type="submit">نشر التعليق</button>
            </form>
        </div>
    </div>
    
    <?php if ($commentAdded && !$xssStored): ?>
        <div class="alert alert-info">✓ تم نشر تعليقك بنجاح! يظهر الآن في قائمة التعليقات أعلاه.</div>
    <?php endif; ?>
    
    <?php if ($xssStored): ?>
        <div class="alert alert-success">تم حفظ التعليق! الآن انتظر زائراً آخر...</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('victim'); ?>" class="btn btn-primary">محاكاة زيارة مستخدم آخر</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'victim'): ?>
    <div class="lab-card" style="background:#2a2a4a;">
        <h2 style="color:#ff9800;">👤 محاكاة: مستخدم آخر يزور المنتدى</h2>
        <p style="color:#aaa;">المستخدم "م. خالد أحمد" - مشرف المنتدى - يتصفح التعليقات الجديدة...</p>
    </div>
    
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-tech-forum.ye/discussions</div></div>
        <div class="app-body">
            <div style="background:#ffebee;padding:10px;border-radius:5px;margin-bottom:15px;">
                🔐 مسجل كـ: <strong>م. خالد أحمد</strong> (مشرف)
            </div>
            <h3>💬 قسم النقاشات العامة</h3>
            <?php foreach ($_SESSION['lab_' . $labKey . '_comments'] as $comment): ?>
                <div style="background: #fff; border: 1px solid #e0e0e0; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <span style="font-size:1.5rem;"><?php echo $comment['avatar']; ?></span>
                        <strong style="color: #667eea;"><?php echo htmlspecialchars($comment['user']); ?></strong>
                    </div>
                    <p style="margin: 0;padding-right:40px;"><?php echo $comment['text']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if ($hasXSS): ?>
        <div class="alert alert-success">تم تنفيذ الكود على متصفح المشرف!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php else: ?>
        <div class="lab-card">
            <p style="color:#ff9800;">التعليق يظهر لكن لم يُنفذ أي كود. تأكد من أن الـ payload ينفذ JavaScript.</p>
            <div class="text-center mt-20">
                <a href="<?php echo stepUrl('forum'); ?>" class="btn btn-secondary">العودة للمنتدى</a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('forum'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color: #bbb; margin-right: 20px; line-height: 2;">
            <li>Stored XSS أخطر لأن الكود يُخزن ويصيب كل زائر</li>
            <li>شائع في: التعليقات، الملفات الشخصية، الرسائل</li>
            <li>الحماية: تنظيف المدخلات عند الحفظ والعرض</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
