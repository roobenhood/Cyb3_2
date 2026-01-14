<?php
/**
 * Lab 2: MIME Type Validation Bypass
 * المستوى: مبتدئ
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'fileupload_lab2';
$folderName = 'fileupload/beginner/lab2_mimetype';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$success = false;
$allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'upload' && isset($_FILES['document'])) {
    $file = $_FILES['document'];
    $filename = $file['name'];
    $mime_type = $file['type'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $php_exts = ['php', 'phtml', 'php5', 'phar'];
    
    if (in_array($mime_type, $allowed_mime)) {
        if (in_array($ext, $php_exts)) {
            $message = 'تم قبول الملف! التحقق من MIME Type وحده غير كافٍ.';
            $success = true;
            $_SESSION['lab_' . $labKey . '_success'] = true;
        } else {
            $message = 'تم رفع المستند بنجاح.';
        }
    } else {
        $message = 'Content-Type غير مقبول: ' . htmlspecialchars($mime_type);
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

$GLOBALS['lab_title'] = 'MIME Type Bypass';
renderLabHeader();
?>

<div class="lab-header">
    <h1>MIME Type Validation Bypass</h1>
    <p>تحدي تجاوز التحقق من Content-Type</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>تقرير الاختبار</h2>
            <div class="scenario-box">
                <p><strong>العميل:</strong> مؤسسة الأرشيف الوطني</p>
                <p><strong>النطاق:</strong> archive.gov.ye/documents/submit</p>
                <p><strong>الوصف:</strong> بوابة إلكترونية لاستقبال المستندات الرسمية من المواطنين.</p>
                <p><strong>الحماية:</strong> يتم التحقق من Content-Type header للملفات المرفوعة.</p>
                <p><strong>المهمة:</strong> اختبار إمكانية تجاوز هذا التحقق.</p>
            </div>
        </div>
        <div class="lab-card">
            <h3>ملاحظة تقنية</h3>
            <p style="color:#aaa;">التطبيق يتحقق من نوع المحتوى المُرسل في الطلب. فكر في مصدر هذه القيمة.</p>
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
        <div class="app-bar"><span>🔒</span><div class="app-url">https://archive.gov.ye/documents/submit</div></div>
        <div class="app-body">
            <h3>تقديم مستند رسمي</h3>
            
            <?php if ($message): ?>
                <div style="background:<?php echo $success?'#1a3a1a':'#2a2a2a';?>;padding:15px;border-radius:8px;margin-bottom:15px;border-right:3px solid <?php echo $success?'#4caf50':'#666';?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success || isset($_SESSION['lab_' . $labKey . '_success'])): ?>
                <div class="text-center mt-20">
                    <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
                </div>
            <?php else: ?>
                <form method="POST" action="<?php echo stepUrl('upload'); ?>" enctype="multipart/form-data" class="app-form">
                    <label style="display:block;margin-bottom:10px;color:#888;">المستند:</label>
                    <input type="file" name="document" required style="margin-bottom:15px;">
                    <div style="color:#666;font-size:12px;margin-bottom:15px;">المقبول: صور، PDF</div>
                    <button type="submit">إرسال</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>تحليل الثغرة</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li><strong>المشكلة:</strong> الاعتماد على بيانات يتحكم بها المستخدم</li>
            <li><strong>السبب:</strong> HTTP headers يمكن تعديلها من قبل العميل</li>
            <li><strong>الأداة:</strong> أي أداة لاعتراض وتعديل الطلبات</li>
        </ul>
    </div>
    <div class="lab-card">
        <h3>التوصيات</h3>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>فحص المحتوى الفعلي للملف (Magic Bytes)</li>
            <li>عدم الثقة بأي بيانات من العميل</li>
            <li>دمج عدة طبقات من التحقق</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
