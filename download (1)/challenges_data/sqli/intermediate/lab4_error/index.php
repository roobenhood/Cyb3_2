<?php
/**
 * SQLi Lab 4 - Error Based
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'sqli_lab4_error';
$folderName = 'sqli/intermediate/lab4_error';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$studentId = $_GET['sid'] ?? '';
$error = '';
$student = null;
$errorBased = false;
$dataExtracted = false;

// Secret data to extract
$secretData = "admin:P@ssw0rd_2024:admin@sanaa-uni.ye";

if ($page === 'students' && $studentId) {
    // Check for error-based injection techniques
    if (preg_match("/EXTRACTVALUE|UPDATEXML|EXP\(|GTID_SUBSET|POLYGON|LINESTRING/i", $studentId)) {
        $errorBased = true;
        $dataExtracted = true;
        $_SESSION['lab_' . $labKey . '_error'] = true;
        $error = "XPATH syntax error: '" . $secretData . "'";
    } elseif (preg_match("/[\'\"].*[\'\"]|--/", $studentId)) {
        // Basic syntax error
        $error = "Error in SQL syntax near '" . htmlspecialchars(substr($studentId, 0, 30)) . "' at line 1";
    } elseif (preg_match("/CONVERT|CAST.*AS/i", $studentId)) {
        $errorBased = true;
        $_SESSION['lab_' . $labKey . '_error'] = true;
        $error = "Conversion failed: " . $secretData;
    } elseif (is_numeric($studentId)) {
        // Normal query
        $students = [
            '1001' => ['name' => 'أحمد محمد', 'major' => 'هندسة برمجيات', 'gpa' => '3.8'],
            '1002' => ['name' => 'سارة علي', 'major' => 'علوم حاسوب', 'gpa' => '3.9'],
            '1003' => ['name' => 'خالد ناصر', 'major' => 'أمن معلومات', 'gpa' => '3.7'],
        ];
        $student = $students[$studentId] ?? null;
        if (!$student) {
            $error = "الطالب برقم $studentId غير موجود في النظام";
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_error'])) {
        $page = 'students';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_error']);
    }
}

$GLOBALS['lab_title'] = 'Error Based SQLi';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Error Based SQL Injection</h1>
    <p>استخراج البيانات عبر رسائل الخطأ</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>نظام البحث عن الطلاب في <strong>جامعة صنعاء</strong> يعرض رسائل خطأ مفصلة.</p>
                <p>لاحظت أن الأخطاء تتضمن معلومات عن الاستعلام.</p>
                <p><strong>الهدف:</strong> استخدم Error-based SQLi لاستخراج بيانات حساسة.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة تقنية</h2>
            <p style="color:#aaa;">بعض أنظمة قواعد البيانات تُظهر معلومات مفيدة في رسائل الخطأ عند استخدام دوال معينة.</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('students'); ?>" class="btn btn-primary">بوابة الطلاب</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'students'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-university.ye/students?id=<?php echo urlencode($studentId); ?></div></div>
        <div class="app-body">
            <h3>🎓 جامعة صنعاء - نظام الطلاب</h3>
            <p style="color:#666;margin-bottom:20px;">شارع الجامعة - صنعاء</p>
            
            <form method="GET" class="app-form">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="students">
                <label style="display:block;margin-bottom:10px;color:#666;">رقم الطالب:</label>
                <input type="text" name="sid" placeholder="مثال: 1001" value="<?php echo htmlspecialchars($studentId); ?>">
                <button type="submit">بحث</button>
            </form>
            
            <?php if ($error): ?>
                <div style="margin-top:20px;padding:15px;background:#ffebee;border:1px solid #ef5350;border-radius:8px;">
                    <div style="color:#c62828;font-weight:bold;margin-bottom:5px;">⚠️ Database Error</div>
                    <code style="font-family:monospace;color:#c62828;word-break:break-all;"><?php echo $error; ?></code>
                </div>
            <?php elseif ($student): ?>
                <div style="margin-top:20px;padding:20px;background:#e8f5e9;border-radius:8px;">
                    <h4 style="margin:0 0 15px 0;color:#2e7d32;">بيانات الطالب</h4>
                    <table style="width:100%;">
                        <tr><td style="padding:5px;color:#666;">الاسم:</td><td style="padding:5px;"><?php echo $student['name']; ?></td></tr>
                        <tr><td style="padding:5px;color:#666;">التخصص:</td><td style="padding:5px;"><?php echo $student['major']; ?></td></tr>
                        <tr><td style="padding:5px;color:#666;">المعدل:</td><td style="padding:5px;"><?php echo $student['gpa']; ?></td></tr>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($dataExtracted || isset($_SESSION['lab_' . $labKey . '_error'])): ?>
        <div class="alert alert-success">استخرجت البيانات عبر رسالة الخطأ!</div>
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
            <li>رسائل الخطأ قد تكشف معلومات حساسة</li>
            <li>بعض الدوال مصممة للتحقق من البيانات يمكن استغلالها</li>
            <li>الحماية: إخفاء رسائل الخطأ التفصيلية عن المستخدم</li>
        </ul>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
