<?php
/**
 * Lab 1: Extension Filter Bypass
 * المستوى: مبتدئ
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'fileupload_lab1';
$folderName = 'fileupload/beginner/lab1_basic';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$success = false;
$allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'upload' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed_ext)) {
        $message = 'تم رفع الملف بنجاح. لا توجد مشكلة أمنية هنا.';
    } else {
        $php_exts = ['phtml', 'php5', 'phar', 'php3', 'php4'];
        if (in_array($ext, $php_exts)) {
            $message = 'تم رفع الملف. السيرفر يعالج هذا الامتداد كـ PHP!';
            $success = true;
            $_SESSION['lab_' . $labKey . '_success'] = true;
        } elseif ($ext === 'php') {
            if (preg_match('/\.(jpg|png|gif)\.php$/i', $filename)) {
                $message = 'تم رفع الملف. الفلتر لم يتحقق من الامتداد الأخير!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
            } else {
                $message = 'امتداد PHP محظور. ابحث عن طرق بديلة.';
            }
        } else {
            $message = 'امتداد غير مسموح.';
        }
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

$GLOBALS['lab_title'] = 'Extension Filter Bypass';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Extension Filter Bypass</h1>
    <p>تحدي تجاوز فلتر الامتدادات</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>تقرير الاختبار</h2>
            <div class="scenario-box">
                <p><strong>العميل:</strong> شركة الخليج للتجارة الإلكترونية</p>
                <p><strong>النطاق:</strong> gulf-commerce.com/product-images/upload</p>
                <p><strong>الوصف:</strong> نظام رفع صور المنتجات. يسمح للبائعين برفع صور بصيغ JPG, PNG, GIF.</p>
                <p><strong>المهمة:</strong> التحقق من فعالية آلية التحقق من نوع الملفات المرفوعة.</p>
            </div>
        </div>
        <div class="lab-card">
            <h3>معلومات تقنية</h3>
            <table style="width:100%;color:#aaa;font-size:14px;">
                <tr><td style="padding:8px;border-bottom:1px solid #333;">السيرفر:</td><td style="padding:8px;border-bottom:1px solid #333;">Apache/2.4.41</td></tr>
                <tr><td style="padding:8px;border-bottom:1px solid #333;">PHP:</td><td style="padding:8px;border-bottom:1px solid #333;">7.4.3</td></tr>
                <tr><td style="padding:8px;">Handler:</td><td style="padding:8px;">mod_php</td></tr>
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
        <div class="app-bar"><span>🔒</span><div class="app-url">https://gulf-commerce.com/seller/upload</div></div>
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
                    <label style="display:block;margin-bottom:10px;color:#888;">اختر ملف الصورة:</label>
                    <input type="file" name="image" required style="margin-bottom:15px;">
                    <div style="color:#666;font-size:12px;margin-bottom:15px;">الصيغ المدعومة: JPG, PNG, GIF</div>
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
            <li><strong>المشكلة:</strong> آلية فحص الامتدادات غير شاملة</li>
            <li><strong>السبب:</strong> السيرفر قد يعالج امتدادات متعددة بنفس الطريقة</li>
            <li><strong>الخطورة:</strong> إمكانية تنفيذ أكواد على السيرفر</li>
        </ul>
    </div>
    <div class="lab-card">
        <h3>التوصيات</h3>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>استخدام Whitelist صارم للامتدادات</li>
            <li>تعطيل تنفيذ PHP في مجلد الرفع</li>
            <li>إعادة تسمية الملفات المرفوعة</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
