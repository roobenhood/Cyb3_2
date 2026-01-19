<?php
/**
 * SQLi Lab 6 - WAF Bypass
 * المستوى: متقدم
 */
ob_start();
require_once dirname(dirname(dirname(__DIR__))) . '/shared/lab_helper.php';
require_once dirname(dirname(dirname(__DIR__))) . '/shared/lab_styles.php';
checkLabLogin();

$labKey = 'sqli_lab6_waf';
$folderName = 'sqli/advanced/lab6_waf';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$query = $_GET['q'] ?? '';
$blocked = false;
$bypassed = false;
$bypassMethod = '';

if ($page === 'search' && $query) {
    // WAF rules - block common keywords
    $blockedKeywords = ['SELECT', 'UNION', 'OR ', ' AND ', '--', '#', '/*', 'DROP', 'INSERT', 'UPDATE', 'DELETE'];
    $blockedPatterns = ['/\bSELECT\b/i', '/\bUNION\b/i', '/\bOR\b/i', '/\bAND\b/i'];
    
    $isBlocked = false;
    foreach ($blockedKeywords as $kw) {
        if (stripos($query, $kw) !== false) {
            $isBlocked = true;
            break;
        }
    }
    
    if ($isBlocked) {
        $blocked = true;
    } else {
        // Bypass detection
        $bypassTechniques = [
            'comments' => '/SEL[\s\/\*]+ECT|UNI[\s\/\*]+ON|AN[\s\/\*]+D|O[\s\/\*]+R/i',
            'encoding' => '/%53%45%4C|%55%4E%49|%4F%52|%41%4E%44/i',
            'case' => '/SeLeCt|uNiOn|AnD|oR/i',
            'null' => '/SEL%00ECT|UNI%00ON/i',
            'newline' => '/SEL\r?\nECT|UNI\r?\nON/i',
            'double' => '/SELSELECTECT|UNUNIONION/i',
            'hex' => '/0x[0-9a-fA-F]+|CHAR\s*\(/i',
        ];
        
        foreach ($bypassTechniques as $method => $pattern) {
            if (preg_match($pattern, $query)) {
                $bypassed = true;
                $bypassMethod = $method;
                $_SESSION['lab_' . $labKey . '_bypassed'] = true;
                break;
            }
        }
        
        // Also accept || as OR alternative
        if (preg_match('/\|\||&&/', $query)) {
            $bypassed = true;
            $bypassMethod = 'operators';
            $_SESSION['lab_' . $labKey . '_bypassed'] = true;
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_bypassed'])) {
        $page = 'search';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_bypassed']);
    }
}

$GLOBALS['lab_title'] = 'WAF Bypass SQLi';
renderLabHeader();
?>

<div class="lab-header">
    <h1>WAF Bypass SQL Injection</h1>
    <p>تجاوز جدار حماية التطبيقات</p>
    <span class="lab-badge badge-advanced">متقدم</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>موقع <strong>شركة يمن موبايل</strong> يستخدم Web Application Firewall (WAF).</p>
                <p>الجدار يحظر الكلمات المفتاحية الشائعة لـ SQL Injection.</p>
                <p><strong>الهدف:</strong> اكتشف طريقة لتجاوز الفلتر وتنفيذ الحقن.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>الكلمات المحظورة</h2>
            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;">
                <span style="background:#ffebee;color:#c62828;padding:5px 10px;border-radius:15px;font-size:0.85rem;">SELECT</span>
                <span style="background:#ffebee;color:#c62828;padding:5px 10px;border-radius:15px;font-size:0.85rem;">UNION</span>
                <span style="background:#ffebee;color:#c62828;padding:5px 10px;border-radius:15px;font-size:0.85rem;">OR</span>
                <span style="background:#ffebee;color:#c62828;padding:5px 10px;border-radius:15px;font-size:0.85rem;">AND</span>
                <span style="background:#ffebee;color:#c62828;padding:5px 10px;border-radius:15px;font-size:0.85rem;">--</span>
                <span style="background:#ffebee;color:#c62828;padding:5px 10px;border-radius:15px;font-size:0.85rem;">#</span>
                <span style="background:#ffebee;color:#c62828;padding:5px 10px;border-radius:15px;font-size:0.85rem;">/*</span>
            </div>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('search'); ?>" class="btn btn-primary">دخول الموقع</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'search'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://yemen-mobile.ye/search?q=<?php echo urlencode($query); ?></div></div>
        <div class="app-body">
            <h3>📱 يمن موبايل - خدمات الاتصالات</h3>
            <p style="color:#666;margin-bottom:15px;">شارع المطار - صنعاء</p>
            
            <div style="background:#fff3cd;padding:10px;border-radius:5px;margin-bottom:15px;font-size:0.85rem;">
                🛡️ هذا الموقع محمي بـ WAF
            </div>
            
            <form method="GET" class="app-form">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="search">
                <input type="text" name="q" placeholder="ابحث عن خدمة أو باقة..." value="<?php echo htmlspecialchars($query); ?>">
                <button type="submit">بحث</button>
            </form>
            
            <?php if ($blocked): ?>
                <div style="margin-top:15px;padding:20px;background:#ffebee;border:2px solid #ef5350;border-radius:8px;text-align:center;">
                    <div style="font-size:2rem;">🚫</div>
                    <div style="color:#c62828;font-weight:bold;margin-top:10px;">WAF ALERT</div>
                    <div style="color:#c62828;font-size:0.9rem;">Blocked: Malicious SQL pattern detected</div>
                    <div style="margin-top:10px;padding:10px;background:#fff;border-radius:5px;font-family:monospace;font-size:0.8rem;color:#666;">
                        Request ID: WAF-<?php echo rand(10000,99999); ?> | Rule: SQL-INJECTION
                    </div>
                </div>
            <?php elseif ($bypassed): ?>
                <div style="margin-top:15px;padding:15px;background:#e8f5e9;border-radius:8px;">
                    <div style="color:#2e7d32;font-weight:bold;margin-bottom:10px;">✓ WAF Bypassed!</div>
                    <div style="background:#1a1a2e;padding:15px;border-radius:8px;font-family:monospace;color:#0f0;">
                        <strong style="color:#fff;">Extracted Data:</strong><br><br>
                        admin | admin@yemen-mobile.ye | Y3m3nM0b!l3_2024<br>
                        operator | ops@yemen-mobile.ye | 0p3r@t0r_P@ss<br>
                        support | help@yemen-mobile.ye | Supp0rt#123
                    </div>
                    <div style="margin-top:10px;font-size:0.85rem;color:#666;">
                        Bypass method: <code><?php echo $bypassMethod; ?></code>
                    </div>
                </div>
            <?php elseif ($query): ?>
                <div style="margin-top:15px;padding:15px;background:#f5f5f5;border-radius:8px;">
                    <p style="color:#666;">نتائج البحث عن: <strong><?php echo htmlspecialchars($query); ?></strong></p>
                    <p style="color:#999;margin-top:10px;">لم يتم العثور على نتائج</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($bypassed || isset($_SESSION['lab_' . $labKey . '_bypassed'])): ?>
        <div class="alert alert-success">تجاوزت الـ WAF بنجاح!</div>
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
            <li>الفلاتر القائمة على الكلمات المفتاحية يمكن تجاوزها</li>
            <li>هناك طرق متعددة لتشفير وإخفاء الـ payloads</li>
            <li>WAF وحده ليس حماية كافية</li>
            <li>الحماية الحقيقية تكون في مستوى الكود (Prepared Statements)</li>
        </ul>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
