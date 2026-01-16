<?php
/**
 * Lab 6: ZIP Symlink Attack
 * المستوى: متقدم
 * استغلال Symbolic Links في ملفات ZIP
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'fileupload_lab6';
$folderName = 'fileupload/advanced/lab6_zip';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$success = false;
$extractedFiles = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'upload' && isset($_FILES['archive'])) {
    $file = $_FILES['archive'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if ($ext !== 'zip') {
        $message = 'يُسمح فقط بملفات ZIP';
    } else {
        $tmpPath = $file['tmp_name'];
        $zip = new ZipArchive();
        
        if ($zip->open($tmpPath) === true) {
            $hasSymlink = false;
            $symlinkTarget = '';
            
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];
                
                // فحص محتوى الملف للتحقق من symlink
                $content = $zip->getFromIndex($i);
                
                // في ZIP الحقيقي، الـ symlinks تُخزن بطريقة خاصة
                // هنا نحاكي ذلك بالتحقق من external_attributes أو المحتوى
                $externalAttr = $stat['external_attr'];
                $unixAttr = ($externalAttr >> 16) & 0xFFFF;
                $isSymlink = (($unixAttr & 0xF000) === 0xA000);
                
                // أو نتحقق من أن الملف يحتوي مسار نظام
                if ($isSymlink || 
                    (strlen($content) < 100 && preg_match('#^(/etc/|/var/|/home/|/root/|\.\./)#', $content))) {
                    $hasSymlink = true;
                    $symlinkTarget = $content ?: $name;
                }
                
                $extractedFiles[] = [
                    'name' => $name,
                    'size' => $stat['size'],
                    'isSymlink' => $isSymlink
                ];
            }
            
            if ($hasSymlink && (strpos($symlinkTarget, '/etc/passwd') !== false || 
                               strpos($symlinkTarget, '/etc/shadow') !== false ||
                               strpos($symlinkTarget, '../') !== false)) {
                $message = 'تم اكتشاف محتوى حساس من خلال Symlink!';
                $success = true;
                $_SESSION['lab_' . $labKey . '_success'] = true;
                $_SESSION['lab_' . $labKey . '_target'] = $symlinkTarget;
            } else {
                $message = 'تم فك ضغط ' . count($extractedFiles) . ' ملفات. لم يتم اكتشاف أي ملفات حساسة.';
            }
            
            $zip->close();
        } else {
            $message = 'فشل فتح ملف ZIP';
        }
    }
}

// طريقة بديلة: التحقق من payload نصي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'manual') {
    $commands = $_POST['commands'] ?? '';
    
    // التحقق من الأوامر الصحيحة
    $hasLnSymlink = preg_match('/ln\s+-s\s+/', $commands);
    $hasTargetFile = preg_match('#(/etc/passwd|/etc/shadow|/var/log|/home/)#', $commands);
    $hasZipSymlinks = preg_match('/zip\s+.*--symlinks/', $commands) || preg_match('/zip\s+-y/', $commands);
    
    if ($hasLnSymlink && $hasTargetFile && $hasZipSymlinks) {
        $message = 'أوامر صحيحة! تم إنشاء ZIP يحتوي symlink خبيث.';
        $success = true;
        $_SESSION['lab_' . $labKey . '_success'] = true;
    } elseif ($hasLnSymlink && $hasTargetFile) {
        $message = 'أنشأت الـ symlink لكن نسيت خيار --symlinks أو -y عند الضغط';
    } elseif ($hasLnSymlink) {
        $message = 'جيد! لكن إلى أين يشير الـ symlink؟';
    } else {
        $message = 'الأوامر غير صحيحة. راجع كيفية إنشاء symbolic links.';
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_success'])) {
        $page = 'upload';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_success'], $_SESSION['lab_' . $labKey . '_target']);
    }
}

$GLOBALS['lab_title'] = 'ZIP Symlink Attack';
renderLabHeader();
?>

<div class="lab-header">
    <h1>ZIP Symlink Attack</h1>
    <p>استغلال ثغرة معالجة الأرشيفات</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>تم تكليفك بفحص نظام <strong>مركز أرشفة الوثائق الحكومية</strong>.</p>
                <p>النظام يسمح للموظفين برفع ملفات ZIP تحتوي وثائق، ثم يقوم بفك ضغطها تلقائياً وعرض المحتويات.</p>
                <p><strong>المعلومة:</strong> الخادم يعمل على Linux ولا يتحقق من نوع الملفات داخل الأرشيف.</p>
            </div>
        </div>
        
        <div class="lab-card">
            <h2>معلومات تقنية</h2>
            <p style="color:#aaa;line-height:1.8;">
                ملفات الأرشيف يمكن أن تحتوي أنواع مختلفة من الملفات. ابحث عن أنواع الملفات الخاصة في أنظمة Unix/Linux وكيف يتم التعامل معها عند فك الضغط.
            </p>
        </div>
        
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('upload'); ?>" class="btn btn-primary">الدخول للنظام</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'upload'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://gov-archive.ye/documents/upload</div></div>
        <div class="app-body">
            <h3>📁 رفع أرشيف وثائق</h3>
            <p style="color:#666;margin-bottom:20px;">ارفع ملف ZIP يحتوي الوثائق المطلوبة</p>
            
            <?php if ($message): ?>
                <div style="background:<?php echo $success?'#e8f5e9':'#fff3cd';?>;padding:15px;border-radius:8px;margin-bottom:15px;color:<?php echo $success?'#2e7d32':'#856404';?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($extractedFiles) && !$success): ?>
                <div style="background:#f5f5f5;padding:15px;border-radius:8px;margin-bottom:15px;">
                    <strong>الملفات المستخرجة:</strong>
                    <ul style="margin:10px 0 0 20px;color:#666;">
                        <?php foreach ($extractedFiles as $f): ?>
                            <li><?php echo htmlspecialchars($f['name']); ?> (<?php echo $f['size']; ?> bytes)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success || isset($_SESSION['lab_' . $labKey . '_success'])): ?>
                <div style="background:#1a1a2e;padding:15px;border-radius:8px;color:#0f0;font-family:monospace;margin-bottom:15px;">
                    <strong style="color:#fff;">محتوى الملف المكشوف:</strong><br><br>
                    root:x:0:0:root:/root:/bin/bash<br>
                    daemon:x:1:1:daemon:/usr/sbin:/usr/sbin/nologin<br>
                    www-data:x:33:33:www-data:/var/www:/usr/sbin/nologin<br>
                    admin:x:1000:1000:System Admin:/home/admin:/bin/bash
                </div>
                <div class="text-center mt-20">
                    <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
                </div>
            <?php else: ?>
                <form method="POST" action="<?php echo stepUrl('upload'); ?>" enctype="multipart/form-data" class="app-form">
                    <input type="file" name="archive" accept=".zip" required style="margin-bottom:15px;">
                    <button type="submit">رفع وفك الضغط</button>
                </form>
                
                <div style="border-top:1px solid #ddd;margin:25px 0;padding-top:20px;">
                    <p style="color:#888;font-size:0.9rem;">أو أدخل الأوامر التي ستستخدمها:</p>
                    <a href="<?php echo stepUrl('manual'); ?>" class="btn btn-outline" style="background:#f0f0f0;color:#333;">الطريقة اليدوية</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'manual'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>💻</span><div class="app-url">Terminal - إنشاء الـ Payload</div></div>
        <div class="app-body" style="background:#1a1a2e;">
            <h3 style="color:#0f0;">$ Attacker Terminal</h3>
            
            <?php if ($message): ?>
                <div style="background:<?php echo $success?'rgba(46,125,50,0.3)':'rgba(255,152,0,0.3)';?>;padding:15px;border-radius:8px;margin-bottom:15px;color:<?php echo $success?'#4caf50':'#ff9800';?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success || isset($_SESSION['lab_' . $labKey . '_success'])): ?>
                <div class="text-center mt-20">
                    <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
                </div>
            <?php else: ?>
                <form method="POST" action="<?php echo stepUrl('manual'); ?>" class="app-form">
                    <label style="display:block;margin-bottom:10px;color:#888;">أدخل أوامر Linux لإنشاء ZIP خبيث:</label>
                    <textarea name="commands" rows="5" placeholder="أدخل الأوامر هنا..." style="width:100%;padding:15px;border-radius:8px;background:#0d0d1a;color:#0f0;border:1px solid #333;font-family:monospace;"><?php echo htmlspecialchars($_POST['commands'] ?? ''); ?></textarea>
                    <button type="submit" style="margin-top:15px;background:#4caf50;">تنفيذ</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('upload'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>ملفات الأرشيف قد تحتوي عناصر خاصة تتجاوز مجلد الاستخراج</li>
            <li>التحقق من المحتوى ضروري قبل فك الضغط</li>
            <li>هناك ثغرات معروفة (CVEs) تستغل هذه التقنية</li>
        </ul>
    </div>
    <div class="lab-card">
        <h2>الحماية</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>فحص محتويات الأرشيف قبل الاستخراج</li>
            <li>التحقق من المسارات بعد الاستخراج</li>
            <li>عزل عملية الاستخراج في بيئة آمنة</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
