<?php
/**
 * Lab 4: Null Byte Injection
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'fileupload_lab4';
$folderName = 'fileupload/intermediate/lab4_nullbyte';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'upload') {
    $filename = $_POST['filename'] ?? '';
    
    if (strpos($filename, '%00') !== false || strpos($filename, "\0") !== false) {
        $parts = preg_split('/(%00|\x00)/', $filename);
        $real_name = $parts[0];
        
        if (preg_match('/\.php\d?$/i', $real_name)) {
            $message = 'تم تخزين الملف! Null Byte قطع الامتداد المزيف.';
            $success = true;
            $_SESSION['lab_' . $labKey . '_success'] = true;
        } else {
            $message = 'Null Byte موجود، لكن الجزء قبله ليس PHP.';
        }
    } else {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed_ext)) {
            $message = 'ملف مقبول. ابحث عن طريقة للتلاعب بالاسم.';
        } else {
            $message = 'امتداد غير مسموح: ' . htmlspecialchars($ext);
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

$GLOBALS['lab_title'] = 'Null Byte Injection';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Null Byte Injection</h1>
    <p>تحدي استغلال الأنظمة القديمة</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>تقرير الاختبار</h2>
            <div class="scenario-box">
                <p><strong>العميل:</strong> مستشفى المدينة المركزي</p>
                <p><strong>النطاق:</strong> hospital-records.local/upload</p>
                <p><strong>الوصف:</strong> نظام أرشفة السجلات الطبية. يعمل على بنية تحتية قديمة.</p>
                <p><strong>البيئة:</strong> PHP 5.3.x على Windows Server 2008</p>
                <p><strong>المهمة:</strong> اختبار ثغرات معالجة أسماء الملفات.</p>
            </div>
        </div>
        <div class="lab-card">
            <h3>سياق تقني</h3>
            <p style="color:#aaa;">في بعض الأنظمة القديمة، توجد أحرف خاصة يمكن أن تؤثر على معالجة أسماء الملفات. ابحث عن كيفية إنهاء السلاسل النصية في لغات البرمجة المختلفة.</p>
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
        <div class="app-bar"><span>🔒</span><div class="app-url">https://hospital-records.local/upload</div></div>
        <div class="app-body">
            <h3>رفع سجل طبي</h3>
            
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
                <form method="POST" action="<?php echo stepUrl('upload'); ?>" class="app-form">
                    <label style="display:block;margin-bottom:10px;color:#888;">اسم الملف:</label>
                    <input type="text" name="filename" placeholder="patient_record.pdf" required style="margin-bottom:15px;">
                    <div style="color:#666;font-size:12px;margin-bottom:15px;">المسموح: صور، PDF</div>
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
            <li><strong>النوع:</strong> ثغرة في معالجة السلاسل النصية</li>
            <li><strong>السبب:</strong> اختلاف التعامل مع الأحرف الخاصة بين الأنظمة</li>
            <li><strong>النتيجة:</strong> تجاوز الامتداد المُضاف تلقائياً</li>
        </ul>
    </div>
    <div class="lab-card">
        <h3>التوصيات</h3>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>تحديث PHP لأحدث إصدار مستقر</li>
            <li>تنظيف أسماء الملفات من الأحرف الخاصة</li>
            <li>استخدام أسماء عشوائية للملفات المرفوعة</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
