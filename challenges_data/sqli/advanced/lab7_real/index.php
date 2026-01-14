<?php
/**
 * SQLi Lab 7 - Real World Attack
 * المستوى: متقدم
 */
ob_start();
require_once dirname(dirname(dirname(__DIR__))) . '/shared/lab_helper.php';
require_once dirname(dirname(dirname(__DIR__))) . '/shared/lab_styles.php';
checkLabLogin();

$labKey = 'sqli_lab7_real';
$folderName = 'sqli/advanced/lab7_real';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

// Initialize session data
if (!isset($_SESSION['lab_' . $labKey . '_balance'])) {
    $_SESSION['lab_' . $labKey . '_balance'] = 150000;
}

// Phase 1: Account lookup
$accountNum = $_GET['acc'] ?? '';
$result = null;
$sqliDetected = false;

if ($page === 'accounts' && $accountNum) {
    if (preg_match("/UNION|OR\s+1|'.*'/i", $accountNum)) {
        $sqliDetected = true;
        $_SESSION['lab_' . $labKey . '_sqli'] = true;
    }
    
    if (is_numeric($accountNum) && strlen($accountNum) >= 4) {
        $result = ['name' => 'محمد أحمد العمري', 'balance' => '150,000', 'branch' => 'شارع تعز'];
    }
}

// Phase 2: Money transfer with stacked queries
$transferred = false;
$transferAmount = 0;
if ($page === 'transfer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['lab_' . $labKey . '_sqli'])) {
        $page = 'accounts';
    } else {
        $amount = $_POST['amount'] ?? '';
        
        if (preg_match("/;\s*(UPDATE|INSERT)/i", $amount)) {
            $transferred = true;
            $_SESSION['lab_' . $labKey . '_transfer'] = true;
            $_SESSION['lab_' . $labKey . '_balance'] = 99999999;
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_transfer'])) {
        $page = 'transfer';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_sqli'], $_SESSION['lab_' . $labKey . '_transfer'], $_SESSION['lab_' . $labKey . '_balance']);
    }
}

$GLOBALS['lab_title'] = 'Real World SQLi Attack';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Real World SQL Injection</h1>
    <p>سيناريو اختراق بنك متكامل</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName, 'أكملت جميع لابات SQL Injection!'); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>المهمة</h2>
            <div class="scenario-box">
                <p>تم التعاقد معك كمختبر اختراق لفحص أمان <strong>بنك الأمل</strong>.</p>
                <p>العميل يريد معرفة ما إذا كان يمكن للمهاجم الوصول لبيانات العملاء أو تعديل الأرصدة.</p>
                <p><strong>الأهداف:</strong></p>
                <ol style="color:#aaa;margin-right:20px;line-height:2;">
                    <li>استخراج بيانات جميع الحسابات</li>
                    <li>تعديل رصيد حساب عبر Stacked Queries</li>
                </ol>
            </div>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('bank'); ?>" class="btn btn-primary">بدء الفحص الأمني</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'bank'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://amal-bank.ye</div></div>
        <div class="app-body">
            <h3>🏦 بنك الأمل</h3>
            <p style="color:#666;">شارع تعز - صنعاء | منذ 2001</p>
            
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;margin:20px 0;">
                <a href="<?php echo stepUrl('accounts'); ?>" style="background:#e3f2fd;padding:20px;border-radius:8px;text-decoration:none;color:#1976d2;text-align:center;">
                    <div style="font-size:1.5rem;">💳</div>
                    <strong>استعلام الحسابات</strong>
                    <small style="display:block;color:#666;margin-top:5px;">التحقق من رصيد الحساب</small>
                </a>
                <a href="<?php echo stepUrl('transfer'); ?>" style="background:#f3e5f5;padding:20px;border-radius:8px;text-decoration:none;color:#7b1fa2;text-align:center;">
                    <div style="font-size:1.5rem;">💸</div>
                    <strong>التحويلات</strong>
                    <small style="display:block;color:#666;margin-top:5px;">تحويل أموال</small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'accounts'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://amal-bank.ye/accounts?acc=<?php echo urlencode($accountNum); ?></div></div>
        <div class="app-body">
            <h3>💳 استعلام الحسابات</h3>
            
            <form method="GET" class="app-form">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="accounts">
                <label style="display:block;margin-bottom:10px;color:#666;">رقم الحساب:</label>
                <input type="text" name="acc" placeholder="مثال: 1001" value="<?php echo htmlspecialchars($accountNum); ?>">
                <button type="submit">استعلام</button>
            </form>
            
            <?php if ($sqliDetected || isset($_SESSION['lab_' . $labKey . '_sqli'])): ?>
                <div style="margin-top:20px;background:#fff3cd;padding:15px;border-radius:8px;">
                    <strong style="color:#856404;">⚠️ تم استخراج جميع الحسابات:</strong>
                    <table style="width:100%;margin-top:10px;border-collapse:collapse;background:#fff;border-radius:5px;">
                        <tr style="background:#f8f9fa;">
                            <th style="padding:8px;text-align:right;border-bottom:1px solid #eee;">رقم الحساب</th>
                            <th style="padding:8px;text-align:right;border-bottom:1px solid #eee;">الاسم</th>
                            <th style="padding:8px;text-align:right;border-bottom:1px solid #eee;">الرصيد</th>
                        </tr>
                        <tr><td style="padding:8px;border-bottom:1px solid #eee;">1001</td><td style="padding:8px;border-bottom:1px solid #eee;">أحمد محمد</td><td style="padding:8px;border-bottom:1px solid #eee;color:#27ae60;">1,500,000</td></tr>
                        <tr><td style="padding:8px;border-bottom:1px solid #eee;">1002</td><td style="padding:8px;border-bottom:1px solid #eee;">سارة علي</td><td style="padding:8px;border-bottom:1px solid #eee;color:#27ae60;">800,000</td></tr>
                        <tr><td style="padding:8px;border-bottom:1px solid #eee;">1003</td><td style="padding:8px;border-bottom:1px solid #eee;">خالد ناصر</td><td style="padding:8px;border-bottom:1px solid #eee;color:#27ae60;">2,300,000</td></tr>
                        <tr><td style="padding:8px;">1004</td><td style="padding:8px;">ياسر أحمد</td><td style="padding:8px;color:#27ae60;">450,000</td></tr>
                    </table>
                </div>
            <?php elseif ($result): ?>
                <div style="margin-top:20px;background:#e8f5e9;padding:15px;border-radius:8px;">
                    <p><strong>الاسم:</strong> <?php echo $result['name']; ?></p>
                    <p><strong>الرصيد:</strong> <?php echo $result['balance']; ?> ر.ي</p>
                    <p><strong>الفرع:</strong> <?php echo $result['branch']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($sqliDetected || isset($_SESSION['lab_' . $labKey . '_sqli'])): ?>
        <div class="alert alert-success">المرحلة 1 مكتملة! حصلت على بيانات جميع العملاء.</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('transfer'); ?>" class="btn btn-primary">المرحلة 2: التحويلات</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('bank'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'transfer'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://amal-bank.ye/transfer</div></div>
        <div class="app-body">
            <h3>💸 تحويل أموال</h3>
            
            <div style="background:#e8f5e9;padding:15px;border-radius:8px;margin-bottom:15px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span style="color:#666;">رصيدك الحالي:</span>
                    <span style="font-size:1.2rem;color:#2e7d32;font-weight:bold;"><?php echo number_format($_SESSION['lab_' . $labKey . '_balance']); ?> ر.ي</span>
                </div>
            </div>
            
            <form method="POST" action="<?php echo stepUrl('transfer'); ?>" class="app-form">
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;color:#666;">من حساب:</label>
                    <input type="text" name="from" value="1001" style="background:#f5f5f5;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;color:#666;">إلى حساب:</label>
                    <input type="text" name="to" value="1002">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;color:#666;">المبلغ:</label>
                    <input type="text" name="amount" placeholder="أدخل المبلغ" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                </div>
                <button type="submit">تنفيذ التحويل</button>
            </form>
            
            <?php if ($transferred || isset($_SESSION['lab_' . $labKey . '_transfer'])): ?>
                <div style="margin-top:15px;background:#e8f5e9;padding:15px;border-radius:8px;">
                    <div style="color:#2e7d32;font-weight:bold;">✓ تم تنفيذ العملية!</div>
                    <div style="margin-top:10px;background:#1a1a2e;padding:10px;border-radius:5px;font-family:monospace;color:#0f0;font-size:0.85rem;">
                        Query: UPDATE accounts SET balance=99999999 WHERE account_id=1001
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($transferred || isset($_SESSION['lab_' . $labKey . '_transfer'])): ?>
        <div class="alert alert-success">المرحلة 2 مكتملة! نجحت في تعديل الرصيد!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('accounts'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName, 'أكملت جميع لابات SQL Injection!'); ?>
    
    <div class="lab-card">
        <h2>ملخص SQL Injection</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li><strong>Login Bypass:</strong> تجاوز المصادقة بـ OR '1'='1'</li>
            <li><strong>UNION:</strong> استخراج بيانات من جداول أخرى</li>
            <li><strong>Blind:</strong> استخراج حرف بحرف عبر true/false</li>
            <li><strong>Error-based:</strong> استخراج عبر رسائل الخطأ</li>
            <li><strong>Stacked:</strong> تنفيذ استعلامات متعددة (الأخطر)</li>
            <li><strong>WAF Bypass:</strong> تجاوز الفلاتر بتقنيات مختلفة</li>
        </ul>
    </div>
    
    <div class="lab-card">
        <h2>الحماية الشاملة</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>Prepared Statements / Parameterized Queries (الحل الوحيد!)</li>
            <li>Least Privilege - صلاحيات محدودة للـ DB user</li>
            <li>Input Validation (ليس بديلاً عن Prepared Statements)</li>
            <li>WAF كطبقة إضافية (ليس كحماية أساسية)</li>
            <li>عدم عرض رسائل خطأ مفصلة للمستخدم</li>
        </ul>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
