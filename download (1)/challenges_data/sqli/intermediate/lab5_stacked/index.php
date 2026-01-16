<?php
/**
 * SQLi Lab 5 - Stacked Queries
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'sqli_lab5_stacked';
$folderName = 'sqli/intermediate/lab5_stacked';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$message = '';
$stacked = false;
$actionType = '';

// Initialize balance if not set
if (!isset($_SESSION['lab_' . $labKey . '_balance'])) {
    $_SESSION['lab_' . $labKey . '_balance'] = 50000;
}

if ($page === 'profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // Detect stacked queries
    if (preg_match("/;\s*INSERT\s+INTO/i", $email)) {
        $stacked = true;
        $actionType = 'INSERT';
        $_SESSION['lab_' . $labKey . '_stacked'] = true;
        $message = 'تم تنفيذ INSERT - أُضيف مستخدم جديد!';
    } elseif (preg_match("/;\s*UPDATE\s+/i", $email)) {
        $stacked = true;
        $actionType = 'UPDATE';
        $_SESSION['lab_' . $labKey . '_stacked'] = true;
        $_SESSION['lab_' . $labKey . '_balance'] = 99999999;
        $message = 'تم تنفيذ UPDATE - تم تعديل البيانات!';
    } elseif (preg_match("/;\s*DELETE\s+/i", $email)) {
        $stacked = true;
        $actionType = 'DELETE';
        $_SESSION['lab_' . $labKey . '_stacked'] = true;
        $message = 'تم تنفيذ DELETE - تم حذف سجلات!';
    } elseif (preg_match("/;\s*DROP\s+/i", $email)) {
        $stacked = true;
        $actionType = 'DROP';
        $_SESSION['lab_' . $labKey . '_stacked'] = true;
        $message = '⚠️ تم تنفيذ DROP - تم حذف جدول كامل!';
    } else {
        $message = 'تم تحديث البريد الإلكتروني بنجاح';
    }
}

if ($page === 'verify') {
    if (!isset($_SESSION['lab_' . $labKey . '_stacked'])) {
        $page = 'profile';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_stacked'], $_SESSION['lab_' . $labKey . '_balance']);
    }
}

$GLOBALS['lab_title'] = 'Stacked Queries SQLi';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Stacked Queries Injection</h1>
    <p>تنفيذ استعلامات متعددة</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>صفحة إعدادات الحساب في <strong>بنك التضامن</strong> تسمح بتحديث البريد الإلكتروني.</p>
                <p>قاعدة البيانات (MSSQL/PostgreSQL) تدعم تنفيذ استعلامات متعددة.</p>
                <p><strong>الهدف:</strong> استخدم Stacked Queries لتنفيذ استعلام إضافي (INSERT/UPDATE/DELETE).</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة تقنية</h2>
            <p style="color:#aaa;">بعض أنظمة قواعد البيانات تسمح بتنفيذ عدة استعلامات في طلب واحد.</p>
            <p style="color:#ff9800;margin-top:10px;">⚠️ هذا يمكن أن يكون خطيراً جداً إذا لم يتم التعامل معه بشكل صحيح!</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('dashboard'); ?>" class="btn btn-primary">دخول لوحة التحكم</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'dashboard'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://tadamon-bank.ye/dashboard</div></div>
        <div class="app-body">
            <h3>🏦 بنك التضامن - لوحة التحكم</h3>
            <p style="color:#666;margin-bottom:20px;">مرحباً، محمد أحمد</p>
            
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin-bottom:20px;">
                <div style="background:#e8f5e9;padding:20px;border-radius:8px;text-align:center;">
                    <div style="font-size:0.9rem;color:#666;">الرصيد الحالي</div>
                    <div style="font-size:1.5rem;color:#2e7d32;font-weight:bold;"><?php echo number_format($_SESSION['lab_' . $labKey . '_balance']); ?> ر.ي</div>
                </div>
                <div style="background:#e3f2fd;padding:20px;border-radius:8px;text-align:center;">
                    <div style="font-size:0.9rem;color:#666;">نوع الحساب</div>
                    <div style="font-size:1.2rem;color:#1976d2;">جاري</div>
                </div>
            </div>
            
            <a href="<?php echo stepUrl('profile'); ?>" style="display:block;background:#f3e5f5;padding:15px;border-radius:8px;text-decoration:none;color:#7b1fa2;text-align:center;">
                ⚙️ إعدادات الحساب
            </a>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'profile'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://tadamon-bank.ye/profile/settings</div></div>
        <div class="app-body">
            <h3>⚙️ إعدادات الحساب</h3>
            <p style="color:#666;margin-bottom:20px;">شارع الرقاص - صنعاء</p>
            
            <?php if ($message): ?>
                <div style="background:<?php echo $stacked ? '#e8f5e9' : '#e3f2fd'; ?>;padding:15px;border-radius:8px;margin-bottom:15px;color:<?php echo $stacked ? '#2e7d32' : '#1976d2'; ?>;">
                    <?php echo $message; ?>
                    <?php if ($actionType): ?>
                        <div style="margin-top:10px;padding:10px;background:rgba(0,0,0,0.1);border-radius:5px;font-family:monospace;font-size:0.85rem;">
                            Query executed: <?php echo $actionType; ?> statement
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo stepUrl('profile'); ?>" class="app-form">
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;color:#666;">الاسم:</label>
                    <input type="text" value="محمد أحمد" disabled style="background:#f5f5f5;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;color:#666;">رقم الحساب:</label>
                    <input type="text" value="1001-2345-6789" disabled style="background:#f5f5f5;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;color:#666;">البريد الإلكتروني:</label>
                    <input type="text" name="email" placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? 'user@tadamon.ye'); ?>">
                </div>
                <button type="submit">حفظ التغييرات</button>
            </form>
        </div>
    </div>
    
    <?php if ($stacked || isset($_SESSION['lab_' . $labKey . '_stacked'])): ?>
        <div class="alert alert-success">نجحت في تنفيذ Stacked Queries!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('verify'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('dashboard'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'verify'): ?>
    <?php renderSuccessBox($folderName); ?>
    
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>بعض قواعد البيانات تدعم تنفيذ استعلامات متعددة</li>
            <li>هذا يفتح إمكانية تعديل أو حذف البيانات</li>
            <li>من أخطر أنواع SQL Injection</li>
            <li>الحماية: Prepared Statements + صلاحيات محدودة</li>
        </ul>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
