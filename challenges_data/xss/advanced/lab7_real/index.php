<?php
/**
 * XSS Lab 7 - Real World Attack
 * المستوى: متقدم
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'xss_lab7_real';
$folderName = 'xss/advanced/lab7_real';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

if (!isset($_SESSION['lab_' . $labKey . '_tickets'])) {
    $_SESSION['lab_' . $labKey . '_tickets'] = [];
}

// إنشاء token للمحاكاة
$_SESSION['lab_csrf_token'] = $_SESSION['lab_csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['lab_employee_balance'] = $_SESSION['lab_employee_balance'] ?? 5000000;

$ticketAdded = false;
if ($page === 'support' && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = $_POST['message'];
    $_SESSION['lab_' . $labKey . '_tickets'][] = [
        'id' => count($_SESSION['lab_' . $labKey . '_tickets']) + 1,
        'subject' => $_POST['subject'] ?? 'استفسار',
        'msg' => $msg, 
        'time' => 'الآن',
        'status' => 'جديد'
    ];
    $ticketAdded = true;
    if (preg_match('/<script|<img|onerror|document\./i', $msg)) {
        $_SESSION['lab_' . $labKey . '_xss_sent'] = true;
    }
}

$exploited = false;
$fundsTransferred = false;
if ($page === 'employee') {
    if (!isset($_SESSION['lab_' . $labKey . '_xss_sent'])) {
        $page = 'support';
    } else {
        foreach ($_SESSION['lab_' . $labKey . '_tickets'] as $t) {
            // تحقق من هجوم سرقة CSRF token أو تحويل أموال
            if (preg_match('/document\.cookie|fetch|XMLHttpRequest|csrf|token/i', $t['msg'])) {
                $exploited = true;
                $_SESSION['lab_' . $labKey . '_exploited'] = true;
            }
            if (preg_match('/transfer|تحويل|balance|رصيد/i', $t['msg'])) {
                $fundsTransferred = true;
            }
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_exploited'])) {
        $page = 'employee';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_tickets'], $_SESSION['lab_' . $labKey . '_xss_sent'], $_SESSION['lab_' . $labKey . '_exploited']);
    }
}

$GLOBALS['lab_title'] = 'Real World XSS Attack';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Real World XSS Attack</h1>
    <p>سيناريو اختراق واقعي متكامل</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName, 'أكملت جميع لابات XSS!'); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>المهمة</h2>
            <div class="scenario-box">
                <p>تم التعاقد معك كمختبر اختراق معتمد لفحص أمان <strong>بنك اليمن الدولي</strong>.</p>
                <p>العميل يريد إثبات ما إذا كان يمكن لمهاجم سرقة بيانات أو أموال عبر XSS.</p>
                <p><strong>الهدف النهائي:</strong> اثبت إمكانية سرقة CSRF token أو تنفيذ عمليات باسم الموظف.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومات الاستطلاع</h2>
            <ul style="color:#aaa;line-height:2;">
                <li>نظام تذاكر الدعم الفني يستقبل رسائل من العملاء</li>
                <li>موظفو الدعم يراجعون التذاكر من لوحة تحكم داخلية</li>
                <li>الموظفون لديهم صلاحيات إجراء تحويلات مالية</li>
            </ul>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('bank'); ?>" class="btn btn-primary">بدء الفحص</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'bank'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://yemen-intl-bank.ye</div></div>
        <div class="app-body">
            <h3>🏦 بنك اليمن الدولي</h3>
            <p style="color:#666;">شارع الزبيري - صنعاء | منذ 1995</p>
            
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin:20px 0;">
                <a href="<?php echo stepUrl('support'); ?>" style="background:#e3f2fd;padding:20px;border-radius:8px;text-decoration:none;color:#1976d2;text-align:center;">
                    <div style="font-size:1.5rem;">💬</div>
                    <strong>الدعم الفني</strong>
                    <small style="display:block;color:#666;">إرسال استفسار</small>
                </a>
                <div style="background:#f0f0f0;padding:20px;border-radius:8px;text-align:center;opacity:0.5;cursor:not-allowed;">
                    <div style="font-size:1.5rem;">💳</div>
                    <strong>الحسابات</strong>
                    <small style="display:block;color:#666;">يتطلب تسجيل دخول</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'support'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://yemen-intl-bank.ye/support/new</div></div>
        <div class="app-body">
            <h3>💬 تذكرة دعم جديدة</h3>
            <p style="color:#666;margin-bottom:15px;">سيقوم أحد موظفينا بمراجعة طلبك خلال 24 ساعة</p>
            
            <form method="POST" action="<?php echo stepUrl('support'); ?>" class="app-form">
                <input type="text" name="subject" placeholder="موضوع التذكرة" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" style="margin-bottom:10px;">
                <textarea name="message" placeholder="اكتب رسالتك للموظف..." rows="5"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                <button type="submit">إرسال التذكرة</button>
            </form>
            
            <p style="color:#888;font-size:0.85rem;margin-top:15px;">
                📧 سيتم إرسال رد على بريدك الإلكتروني
            </p>
        </div>
    </div>
    
    <?php if ($ticketAdded && !isset($_SESSION['lab_' . $labKey . '_xss_sent'])): ?>
        <div class="alert alert-info">✓ تم إرسال تذكرتك بنجاح! سيتم مراجعتها قريباً.</div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['lab_' . $labKey . '_xss_sent'])): ?>
        <div class="alert alert-success">تم إرسال التذكرة! تحتوي على محتوى مثير للاهتمام...</div>
        <div class="lab-card">
            <p>الموظف سيراجع التذكرة الآن من لوحة التحكم الداخلية.</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('employee'); ?>" class="btn btn-primary">محاكاة فتح الموظف للتذكرة</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('bank'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'employee'): ?>
    <div class="lab-card" style="background:#2a2a4a;">
        <h2 style="color:#ff9800;">👤 محاكاة: الموظف يفتح التذاكر</h2>
        <p style="color:#aaa;">الموظف "سعيد أحمد" يفتح لوحة التحكم لمراجعة التذاكر الجديدة...</p>
    </div>
    
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://yemen-intl-bank.ye/internal/tickets</div></div>
        <div class="app-body">
            <div style="background:#ffebee;padding:10px;border-radius:5px;margin-bottom:15px;">
                🔐 <strong>Employee Panel</strong> | سعيد أحمد - قسم الدعم الفني
            </div>
            
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h3 style="margin:0;">📋 التذاكر الواردة</h3>
                <span style="background:#e3f2fd;padding:5px 10px;border-radius:15px;font-size:0.85rem;">
                    <?php echo count($_SESSION['lab_' . $labKey . '_tickets']); ?> تذكرة
                </span>
            </div>
            
            <!-- معلومات حساسة مخفية في الصفحة -->
            <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['lab_csrf_token']; ?>">
            <input type="hidden" id="employee_id" value="EMP-2847">
            
            <?php foreach ($_SESSION['lab_' . $labKey . '_tickets'] as $t): ?>
                <div style="background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:10px;border-right:4px solid #1976d2;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                        <strong>تذكرة #<?php echo $t['id']; ?>: <?php echo htmlspecialchars($t['subject']); ?></strong>
                        <span style="background:#fff3cd;padding:3px 8px;border-radius:10px;font-size:0.8rem;"><?php echo $t['status']; ?></span>
                    </div>
                    <small style="color:#888;"><?php echo $t['time']; ?></small>
                    <div style="margin-top:10px;padding:10px;background:#fff;border-radius:5px;">
                        <?php echo $t['msg']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($_SESSION['lab_' . $labKey . '_tickets'])): ?>
                <p style="color:#888;text-align:center;padding:30px;">لا توجد تذاكر جديدة</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($exploited || isset($_SESSION['lab_' . $labKey . '_exploited'])): ?>
        <div class="alert alert-success">تم تنفيذ الهجوم على جهاز الموظف!</div>
        
        <?php if ($fundsTransferred): ?>
            <div class="lab-card" style="background:#2e7d32;color:#fff;">
                <h3>💰 تم تحويل الأموال!</h3>
                <p>نجحت في تنفيذ عملية تحويل باستخدام صلاحيات الموظف.</p>
            </div>
        <?php endif; ?>
        
        <div class="lab-card">
            <h2>البيانات المسروقة</h2>
            <div style="background:#1a1a2e;padding:15px;border-radius:8px;font-family:monospace;color:#0f0;">
                CSRF Token: <?php echo $_SESSION['lab_csrf_token']; ?><br>
                Employee ID: EMP-2847<br>
                Session: <?php echo session_id(); ?>
            </div>
        </div>
        
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php else: ?>
        <div class="lab-card">
            <p style="color:#ff9800;">الـ payload لم يحقق الهدف. حاول سرقة CSRF token أو تنفيذ عملية.</p>
            <div class="text-center mt-20">
                <a href="<?php echo stepUrl('support'); ?>" class="btn btn-secondary">حاول مرة أخرى</a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('support'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName, 'أكملت جميع لابات XSS!'); ?>
    
    <div class="lab-card">
        <h2>ملخص ما تعلمته في XSS</h2>
        <ul style="color: #bbb; line-height: 2;">
            <li><strong>Reflected:</strong> ينعكس من الطلب مباشرة - يتطلب خداع الضحية بالرابط</li>
            <li><strong>Stored:</strong> يُخزن ويصيب كل زائر - الأخطر</li>
            <li><strong>DOM:</strong> يحدث في JavaScript - لا يظهر في HTML</li>
            <li><strong>Filter Bypass:</strong> Blacklists لا تكفي أبداً</li>
            <li><strong>Cookie Theft:</strong> سرقة الجلسات - HttpOnly يمنعها</li>
            <li><strong>CSP Bypass:</strong> إعدادات CSP الضعيفة قابلة للاستغلال</li>
        </ul>
    </div>
    
    <div class="lab-card">
        <h2>الحماية الشاملة</h2>
        <ul style="color: #bbb; line-height: 2;">
            <li>Output Encoding في كل مكان</li>
            <li>Content-Security-Policy صارم</li>
            <li>HttpOnly + Secure + SameSite للكوكيز</li>
            <li>Input Validation (Whitelist)</li>
        </ul>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
