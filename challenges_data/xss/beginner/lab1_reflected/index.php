<?php
/**
 * XSS Lab 1 - Reflected XSS
 * المستوى: مبتدئ
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'xss_lab1_reflected';
$folderName = 'xss/beginner/lab1_reflected';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $_SESSION['current_challenge_id'] = $_GET['id'];
}

initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$searchQuery = $_GET['q'] ?? '';
$xssDetected = false;
if ($page === 'search' && !empty($searchQuery)) {
    if (preg_match('/<script|<img|<svg|<body|<iframe|onerror|onload|onclick|onmouseover/i', $searchQuery)) {
        $xssDetected = true;
        $_SESSION['lab_' . $labKey . '_xss_found'] = true;
    }
}

$message = $_GET['msg'] ?? '';
$exploitSuccess = false;
if ($page === 'notification' && !isset($_SESSION['lab_' . $labKey . '_xss_found'])) {
    $page = 'search';
}
if ($page === 'notification' && !empty($message)) {
    if (preg_match('/alert\s*\(|confirm\s*\(|prompt\s*\(/i', $message)) {
        $exploitSuccess = true;
        $_SESSION['lab_' . $labKey . '_exploited'] = true;
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_exploited'])) {
        $page = 'notification';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_xss_found'], $_SESSION['lab_' . $labKey . '_exploited']);
    }
}

$products = [
    ['name' => 'iPhone 15 Pro', 'price' => '450,000', 'stock' => 5],
    ['name' => 'Samsung Galaxy S24', 'price' => '380,000', 'stock' => 8],
    ['name' => 'MacBook Pro M3', 'price' => '800,000', 'stock' => 3],
    ['name' => 'AirPods Pro', 'price' => '75,000', 'stock' => 15],
];

$GLOBALS['lab_title'] = 'Reflected XSS';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Reflected XSS</h1>
    <p>اختبار أمان موقع تجارة إلكترونية</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName, 'أكملت هذا التحدي'); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>تم التعاقد معك لإجراء اختبار اختراق على موقع <strong>متجر صنعاء للإلكترونيات</strong>.</p>
                <p>العميل قلق بشأن أمان خاصية البحث في الموقع.</p>
                <p><strong>المهمة:</strong> اختبر ما إذا كان يمكن حقن كود JavaScript عبر خاصية البحث.</p>
            </div>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('search'); ?>" class="btn btn-primary">بدء الاختبار</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'search'): ?>
    <div class="vuln-app">
        <div class="app-bar">
            <span>🔒</span>
            <div class="app-url">https://sanaa-electronics.ye/search?q=<?php echo urlencode($searchQuery); ?></div>
        </div>
        <div class="app-body">
            <h3 style="margin: 0;">🔍 البحث عن منتجات</h3>
            <form method="GET" class="app-form" style="margin-top: 15px;">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="search">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="q" placeholder="ابحث عن منتج..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex: 1;">
                    <button type="submit">بحث</button>
                </div>
            </form>
            <?php if (!empty($searchQuery)): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                    <p style="margin-bottom: 10px;">نتائج البحث عن: <strong><?php echo $searchQuery; ?></strong></p>
                    <?php
                    $found = false;
                    foreach ($products as $product) {
                        if (stripos($product['name'], strip_tags($searchQuery)) !== false) {
                            $found = true;
                            echo '<div style="background: #fff; padding: 10px; margin: 5px 0; border-radius: 5px;">';
                            echo '<span>' . htmlspecialchars($product['name']) . '</span> - ';
                            echo '<span style="color: #27ae60;">' . $product['price'] . ' ر.ي</span></div>';
                        }
                    }
                    if (!$found) echo '<p style="color: #999;">لم يتم العثور على نتائج</p>';
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($xssDetected): ?>
        <div class="alert alert-success">وجدت نقطة الإدخال! الآن جرب صفحة أخرى.</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('notification'); ?>" class="btn btn-primary">انتقل لصفحة الإشعارات</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'notification'): ?>
    <div class="vuln-app">
        <div class="app-bar">
            <span>🔒</span>
            <div class="app-url">https://sanaa-electronics.ye/notifications</div>
        </div>
        <div class="app-body">
            <h3 style="margin: 0;">🔔 الإشعارات</h3>
            <?php if (!empty($message)): ?>
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <strong>إشعار جديد:</strong> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="GET" class="app-form" style="margin-top: 15px;">
                <input type="hidden" name="id" value="<?php echo $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? ''); ?>">
                <input type="hidden" name="step" value="notification">
                <input type="text" name="msg" placeholder="أدخل رسالة..." value="<?php echo htmlspecialchars($message); ?>">
                <button type="submit">إرسال</button>
            </form>
        </div>
    </div>
    
    <?php if ($exploitSuccess): ?>
        <div class="alert alert-success">نجح الاستغلال!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('search'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color: #bbb; margin-right: 20px; line-height: 2;">
            <li>Reflected XSS يحدث عندما تنعكس المدخلات في الصفحة بدون تنظيف</li>
            <li>يتطلب خداع الضحية للنقر على رابط خبيث</li>
            <li>الحماية: استخدام htmlspecialchars() و Content-Security-Policy</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
