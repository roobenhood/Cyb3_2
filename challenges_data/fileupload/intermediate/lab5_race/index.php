<?php
/**
 * Lab 5: Race Condition Attack
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'fileupload_lab5';
$folderName = 'fileupload/intermediate/lab5_race';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$success = false;
$file_status = $_SESSION['race_status'] ?? 'idle';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'upload') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload') {
        $_SESSION['race_status'] = 'uploaded';
        $_SESSION['race_upload_time'] = microtime(true);
        $message = 'جاري معالجة الملف... [Scanning for malware...]';
        $file_status = 'uploaded';
    }
    elseif ($action === 'execute') {
        if (isset($_SESSION['race_status']) && $_SESSION['race_status'] === 'uploaded') {
            $elapsed = microtime(true) - $_SESSION['race_upload_time'];
            
            if ($elapsed < 2.5) {
                $message = "تم تنفيذ الملف! الوقت: " . number_format($elapsed, 2) . "s";
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
                unset($_SESSION['race_status']);
            } else {
                $message = "تم حذف الملف قبل الوصول إليه. الوقت: " . number_format($elapsed, 2) . "s";
                unset($_SESSION['race_status']);
                $file_status = 'idle';
            }
        } else {
            $message = 'لا يوجد ملف مرفوع حالياً.';
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

$GLOBALS['lab_title'] = 'Race Condition';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Race Condition Attack</h1>
    <p>تحدي استغلال النافذة الزمنية</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>تقرير الاختبار</h2>
            <div class="scenario-box">
                <p><strong>العميل:</strong> شركة CloudShare للتخزين السحابي</p>
                <p><strong>النطاق:</strong> cloudshare.io/api/upload</p>
                <p><strong>الوصف:</strong> خدمة مشاركة الملفات مع فحص للبرمجيات الخبيثة.</p>
                <p><strong>آلية العمل:</strong> الملفات تُرفع أولاً، ثم تُفحص، ثم تُحذف إذا كانت خبيثة.</p>
                <p><strong>المهمة:</strong> استغلال الفترة الزمنية بين الرفع والحذف.</p>
            </div>
        </div>
        <div class="lab-card">
            <h3>تدفق العملية</h3>
            <div style="font-family:monospace;color:#aaa;font-size:13px;">
                <div style="padding:8px;border-right:2px solid #4caf50;">1. استقبال الملف → حفظ في /uploads/</div>
                <div style="padding:8px;border-right:2px solid #ff9800;">2. إرسال للفحص → ClamAV scan (~2-3 ثواني)</div>
                <div style="padding:8px;border-right:2px solid #f44336;">3. نتيجة الفحص → حذف إذا ملف خبيث</div>
            </div>
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
        <div class="app-bar"><span>🔒</span><div class="app-url">https://cloudshare.io/upload</div></div>
        <div class="app-body">
            <h3>CloudShare - رفع ملف</h3>
            
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
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <form method="POST" action="<?php echo stepUrl('upload'); ?>">
                        <input type="hidden" name="action" value="upload">
                        <button type="submit" style="width:100%;padding:15px;background:#2196f3;border:none;color:white;border-radius:8px;cursor:pointer;">
                            رفع shell.php
                        </button>
                    </form>
                    
                    <form method="POST" action="<?php echo stepUrl('upload'); ?>">
                        <input type="hidden" name="action" value="execute">
                        <button type="submit" style="width:100%;padding:15px;background:#ff5722;border:none;color:white;border-radius:8px;cursor:pointer;">
                            تنفيذ الملف
                        </button>
                    </form>
                </div>
                
                <div style="margin-top:20px;padding:15px;background:#1a1a2e;border-radius:8px;">
                    <div style="color:#888;font-size:13px;">
                        الحالة: 
                        <span style="color:<?php echo $file_status === 'uploaded' ? '#4caf50' : '#666'; ?>">
                            <?php echo $file_status === 'uploaded' ? '● ملف موجود (يُفحص الآن...)' : '○ لا يوجد ملف'; ?>
                        </span>
                    </div>
                </div>
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
            <li><strong>النوع:</strong> ثغرة في توقيت العمليات</li>
            <li><strong>المشكلة:</strong> وجود فترة زمنية بين الرفع والفحص</li>
            <li><strong>الاستغلال:</strong> استغلال النافذة الزمنية قبل اكتمال الفحص</li>
        </ul>
    </div>
    <div class="lab-card">
        <h3>التوصيات</h3>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>رفع الملفات لمجلد مؤقت غير قابل للوصول</li>
            <li>نقل الملف للمجلد النهائي بعد الفحص</li>
            <li>استخدام Atomic operations</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
