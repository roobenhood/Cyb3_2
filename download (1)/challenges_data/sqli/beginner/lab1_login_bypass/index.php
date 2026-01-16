<?php
/**
 * SQLi Lab 1 - Login Bypass
 * المستوى: مبتدئ
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'sqli_lab1_basic';
$folderName = 'sqli/beginner/lab1_login_bypass';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$error = '';
$loginSuccess = false;
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // محاكاة الثغرة
    if (preg_match("/('|\")\s*(OR|AND)\s*('|\"|[0-9])/i", $username . $password) ||
        preg_match("/--|\#|\/\*/", $username . $password) ||
        preg_match("/'='|\"=\"|1=1|1='1/i", $username . $password)) {
        $loginSuccess = true;
        $_SESSION['lab_' . $labKey . '_bypassed'] = true;
    } else {
        $error = 'بيانات الدخول غير صحيحة';
    }
}

if ($page === 'dashboard' && !isset($_SESSION['lab_' . $labKey . '_bypassed'])) {
    $page = 'login';
}

if ($page === 'employees') {
    if (!isset($_SESSION['lab_' . $labKey . '_bypassed'])) {
        $page = 'login';
    } else {
        markLabCompleted($folderName);
    }
}

$GLOBALS['lab_title'] = 'SQL Injection - Login Bypass';
renderLabHeader();
?>

<div class="lab-header">
    <h1>SQL Injection - Login Bypass</h1>
    <p>تجاوز نظام المصادقة</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>تم تكليفك باختبار أمان بوابة <strong>شركة صنعاء للتوظيف</strong>.</p>
                <p>لاحظت أن نموذج تسجيل الدخول قد يكون عرضة لـ SQL Injection.</p>
                <p><strong>الهدف:</strong> ادخل للوحة التحكم بدون معرفة كلمة المرور الصحيحة.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة تقنية</h2>
            <p style="color:#aaa;">عندما يتم دمج مدخلات المستخدم مباشرة في استعلام SQL بدون تنظيف، يمكن للمهاجم تعديل منطق الاستعلام.</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('login'); ?>" class="btn btn-primary">صفحة تسجيل الدخول</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'login'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-jobs.ye/admin/login</div></div>
        <div class="app-body">
            <h3>🔐 بوابة الموظفين</h3>
            <p style="color:#666;margin-bottom:20px;">شركة صنعاء للتوظيف - شارع الزبيري</p>
            
            <?php if ($error): ?>
                <div style="background:#ffebee;color:#c62828;padding:10px;border-radius:5px;margin-bottom:15px;"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo stepUrl('login'); ?>" class="app-form">
                <input type="text" name="username" placeholder="اسم المستخدم" autocomplete="off">
                <input type="password" name="password" placeholder="كلمة المرور" autocomplete="off">
                <button type="submit">دخول</button>
            </form>
            
            <p style="color:#999;font-size:0.85rem;margin-top:15px;">نسيت كلمة المرور؟ تواصل مع قسم IT</p>
        </div>
    </div>
    
    <?php if ($loginSuccess): ?>
        <div class="alert alert-success">تم تجاوز نظام المصادقة!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('dashboard'); ?>" class="btn btn-primary">دخول لوحة التحكم</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'dashboard'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-jobs.ye/admin/dashboard</div></div>
        <div class="app-body">
            <div style="background:#e8f5e9;padding:10px;border-radius:5px;margin-bottom:15px;">✓ مرحباً بك في لوحة التحكم!</div>
            <h3>📊 لوحة تحكم الإدارة</h3>
            
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin:20px 0;">
                <div style="background:#f8f9fa;padding:20px;border-radius:8px;text-align:center;">
                    <div style="font-size:2rem;color:#667eea;">247</div>
                    <p>طلب توظيف</p>
                </div>
                <div style="background:#f8f9fa;padding:20px;border-radius:8px;text-align:center;">
                    <div style="font-size:2rem;color:#27ae60;">58</div>
                    <p>وظيفة متاحة</p>
                </div>
            </div>
            
            <a href="<?php echo stepUrl('employees'); ?>" style="display:block;background:#e3f2fd;padding:15px;border-radius:8px;text-decoration:none;color:#1976d2;text-align:center;">
                👥 قائمة الموظفين والرواتب
            </a>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('login'); ?>" class="btn btn-secondary">العودة</a>
        <a href="<?php echo stepUrl('employees'); ?>" class="btn btn-primary">عرض الموظفين</a>
    </div>

<?php elseif ($page === 'employees'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-jobs.ye/admin/employees</div></div>
        <div class="app-body">
            <h3>👥 بيانات الموظفين السرية</h3>
            <table style="width:100%;border-collapse:collapse;margin-top:15px;">
                <tr style="background:#f8f9fa;">
                    <th style="padding:10px;text-align:right;">الاسم</th>
                    <th style="padding:10px;text-align:right;">المنصب</th>
                    <th style="padding:10px;text-align:right;">الراتب</th>
                </tr>
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #eee;">أحمد محمد العمري</td>
                    <td style="padding:10px;border-bottom:1px solid #eee;">مدير عام</td>
                    <td style="padding:10px;border-bottom:1px solid #eee;">450,000 ر.ي</td>
                </tr>
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #eee;">سارة علي الحميري</td>
                    <td style="padding:10px;border-bottom:1px solid #eee;">مدير HR</td>
                    <td style="padding:10px;border-bottom:1px solid #eee;">350,000 ر.ي</td>
                </tr>
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #eee;">خالد ناصر السعيدي</td>
                    <td style="padding:10px;border-bottom:1px solid #eee;">محاسب</td>
                    <td style="padding:10px;border-bottom:1px solid #eee;">280,000 ر.ي</td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php renderSuccessBox($folderName); ?>
    
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>دمج المدخلات مباشرة في الاستعلام يفتح ثغرات خطيرة</li>
            <li>يمكن تعديل منطق الاستعلام بطرق مختلفة</li>
            <li>الحماية: استخدام Prepared Statements حصرياً</li>
        </ul>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
