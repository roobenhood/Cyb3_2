<?php
/**
 * XSS Lab 6 - CSP Bypass
 * المستوى: متقدم
 */
ob_start();

$page = $_GET['step'] ?? 'intro';
if ($page === 'portal') {
    header("Content-Security-Policy: script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;");
}

require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'xss_lab6_csp';
$folderName = 'xss/advanced/lab6_csp';
initLabSession($labKey);

$solved = isLabSolved($folderName);

$input = $_GET['search'] ?? '';
$bypassed = false;
$stage2 = false;

if ($page === 'portal' && !empty($input)) {
    // المرحلة 1: استخدام JSONP أو مكتبات من CDN
    if (preg_match('/cdnjs\.cloudflare\.com/i', $input)) {
        $bypassed = true;
        $_SESSION['lab_' . $labKey . '_stage1'] = true;
    }
}

if ($page === 'stage2') {
    if (!isset($_SESSION['lab_' . $labKey . '_stage1'])) {
        $page = 'portal';
    }
}

if ($page === 'stage2' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = $_POST['payload'] ?? '';
    // المرحلة 2: استخدام Angular أو require.js للتنفيذ
    if (preg_match('/angular|require|callback|jsonp/i', $payload)) {
        $stage2 = true;
        $_SESSION['lab_' . $labKey . '_bypassed'] = true;
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_bypassed'])) {
        $page = 'stage2';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_bypassed'], $_SESSION['lab_' . $labKey . '_stage1']);
    }
}

$GLOBALS['lab_title'] = 'CSP Bypass';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Content Security Policy Bypass</h1>
    <p>تجاوز سياسة أمان المحتوى</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>موقع <strong>بوابة وزارة الاتصالات</strong> يستخدم Content Security Policy للحماية من XSS.</p>
                <p>الموقع يسمح بتحميل scripts من cdnjs.cloudflare.com فقط.</p>
                <p><strong>المهمة:</strong> استغل هذا الإعداد لتجاوز الحماية.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة تقنية</h2>
            <p style="color:#aaa;">CSP يحدد المصادر المسموحة لتحميل الموارد. افحص الـ headers لفهم السياسة المطبقة.</p>
            <div style="background:#1a1a2e;padding:10px;border-radius:5px;margin-top:10px;font-family:monospace;color:#0f0;font-size:0.85rem;">
                Content-Security-Policy: script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com
            </div>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('portal'); ?>" class="btn btn-primary">دخول البوابة</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'portal'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://gov-portal.ye/search</div></div>
        <div class="app-body">
            <h3>🏛️ بوابة وزارة الاتصالات وتقنية المعلومات</h3>
            <p style="color:#666;margin-bottom:15px;">شارع الستين - صنعاء</p>
            
            <div style="background:#e8f5e9;padding:10px;border-radius:5px;margin-bottom:15px;font-size:0.9rem;">
                🛡️ هذا الموقع محمي بـ CSP
            </div>
            
            <form method="GET" class="app-form">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="portal">
                <input type="text" name="search" placeholder="ابحث في الخدمات..." value="<?php echo htmlspecialchars($input); ?>">
                <button type="submit">بحث</button>
            </form>
            
            <?php if ($input): ?>
                <div style="margin-top:15px;padding:15px;background:#f5f5f5;border-radius:8px;">
                    <strong>نتائج البحث عن:</strong> <?php echo $input; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($bypassed || isset($_SESSION['lab_' . $labKey . '_stage1'])): ?>
        <div class="alert alert-success">جيد! وجدت طريقة لتحميل script خارجي!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('stage2'); ?>" class="btn btn-primary">المرحلة التالية</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'stage2'): ?>
    <div class="lab-card">
        <h2>المرحلة 2: تنفيذ الكود</h2>
        <p style="color:#aaa;">كيف يمكنك الاستفادة من CDN المسموح لتنفيذ كود JavaScript؟</p>
    </div>
    
    <div class="vuln-app">
        <div class="app-bar"><span>💻</span><div class="app-url">Exploit Development</div></div>
        <div class="app-body" style="background:#1a1a2e;">
            <h3 style="color:#0f0;">اشرح طريقة الاستغلال</h3>
            
            <?php if ($stage2): ?>
                <div style="background:rgba(46,125,50,0.3);padding:15px;border-radius:8px;color:#4caf50;margin-bottom:15px;">
                    ✓ استراتيجية صحيحة! يمكنك استخدام مكتبات مثل Angular لتنفيذ كود عشوائي.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo stepUrl('stage2'); ?>" class="app-form">
                <textarea name="payload" rows="4" placeholder="اشرح كيف ستستغل CDN المسموح لتنفيذ JavaScript... (اذكر المكتبة أو التقنية)" style="width:100%;padding:15px;background:#0d0d1a;color:#0f0;border:1px solid #333;font-family:monospace;"><?php echo htmlspecialchars($_POST['payload'] ?? ''); ?></textarea>
                <button type="submit" style="margin-top:15px;background:#4caf50;">تحقق</button>
            </form>
        </div>
    </div>
    
    <?php if ($stage2 || isset($_SESSION['lab_' . $labKey . '_bypassed'])): ?>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('portal'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color: #bbb; line-height: 2;">
            <li>سياسات CSP الضعيفة يمكن تجاوزها</li>
            <li>السماح بمصادر خارجية قد يفتح ثغرات</li>
            <li>بعض المكتبات يمكن استخدامها لتنفيذ كود عشوائي</li>
            <li>الحماية: سياسة صارمة مع nonce أو hash</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
