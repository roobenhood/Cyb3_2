<?php
/**
 * Lab 3: Magic Bytes / Polyglot Files
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'fileupload_lab3';
$folderName = 'fileupload/intermediate/lab3_content';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$success = false;

$magic_bytes = [
    'gif' => ['GIF87a', 'GIF89a'],
    'png' => ["\x89PNG"],
    'jpg' => ["\xFF\xD8\xFF"]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'upload' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $content = file_get_contents($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $is_valid_image = false;
    $detected_type = '';
    foreach ($magic_bytes as $type => $signatures) {
        foreach ($signatures as $sig) {
            if (strpos($content, $sig) === 0) {
                $is_valid_image = true;
                $detected_type = strtoupper($type);
                break 2;
            }
        }
    }
    
    if ($is_valid_image) {
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            $message = "تم قبول الملف كـ {$detected_type}، لكنه يحتوي على كود PHP قابل للتنفيذ!";
            $success = true;
            $_SESSION['lab_' . $labKey . '_success'] = true;
        } else {
            $message = "ملف {$detected_type} صالح. لا يوجد محتوى خبيث.";
        }
    } else {
        $message = 'الملف لا يطابق أي توقيع صورة معروف.';
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

$GLOBALS['lab_title'] = 'Polyglot File Attack';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Polyglot File Attack</h1>
    <p>تحدي إنشاء ملف متعدد الأنواع</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>تقرير الاختبار</h2>
            <div class="scenario-box">
                <p><strong>العميل:</strong> منصة المزادات الإلكترونية</p>
                <p><strong>النطاق:</strong> auctions-platform.com/item/upload</p>
                <p><strong>الوصف:</strong> نظام رفع صور المنتجات المعروضة للمزاد.</p>
                <p><strong>الحماية:</strong> فحص Magic Bytes للتأكد من أن الملف صورة حقيقية.</p>
                <p><strong>المهمة:</strong> إنشاء ملف يجتاز فحص المحتوى ويحتوي على كود قابل للتنفيذ.</p>
            </div>
        </div>
        <div class="lab-card">
            <h3>معلومات عن فحص المحتوى</h3>
            <p style="color:#aaa;">النظام يفحص بداية الملف للتحقق من نوعه الحقيقي. ابحث عن ما يُميز كل نوع من الملفات.</p>
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
        <div class="app-bar"><span>🔒</span><div class="app-url">https://auctions-platform.com/item/upload</div></div>
        <div class="app-body">
            <h3>رفع صورة المنتج</h3>
            
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
                    <label style="display:block;margin-bottom:10px;color:#888;">ملف الصورة:</label>
                    <input type="file" name="file" required style="margin-bottom:15px;">
                    <div style="color:#666;font-size:12px;margin-bottom:15px;">يتم فحص محتوى الملف للتأكد من صحته</div>
                    <button type="submit">رفع</button>
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
            <li><strong>التقنية:</strong> Polyglot File - ملف صالح لأكثر من نوع</li>
            <li><strong>المشكلة:</strong> فحص البداية فقط دون المحتوى الكامل</li>
            <li><strong>النتيجة:</strong> ملف يُعامل كصورة ويُنفذ كـ PHP</li>
        </ul>
    </div>
    <div class="lab-card">
        <h3>التوصيات</h3>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>استخدام مكتبات معالجة الصور لإعادة إنشاء الملف</li>
            <li>منع تنفيذ السكربتات في مجلد الرفع</li>
            <li>فصل مجلد الرفع عن الـ Web Root</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
