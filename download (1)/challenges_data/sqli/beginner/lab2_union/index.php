<?php
/**
 * SQLi Lab 2 - UNION Based
 * المستوى: مبتدئ
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'sqli_lab2_union';
$folderName = 'sqli/beginner/lab2_union';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

$productId = $_GET['pid'] ?? '';
$results = [];
$unionDetected = false;
$columnsFound = false;
$dataExtracted = false;

// منتجات وهمية
$products = [
    '1' => ['id' => 1, 'name' => 'iPhone 15 Pro Max', 'price' => '650,000', 'desc' => 'أحدث إصدار من Apple'],
    '2' => ['id' => 2, 'name' => 'Samsung Galaxy S24 Ultra', 'price' => '580,000', 'desc' => 'تجربة Android المثالية'],
    '3' => ['id' => 3, 'name' => 'MacBook Pro M3', 'price' => '1,200,000', 'desc' => 'للمحترفين والمبدعين'],
];

if ($page === 'product' && $productId) {
    // التحقق من محاولات UNION
    if (preg_match('/UNION\s+(ALL\s+)?SELECT/i', $productId)) {
        $unionDetected = true;
        $_SESSION['lab_' . $labKey . '_union'] = true;
        
        // التحقق من عدد الأعمدة الصحيح (3 أعمدة)
        if (preg_match('/UNION\s+(ALL\s+)?SELECT\s+\S+\s*,\s*\S+\s*,\s*\S+/i', $productId)) {
            $columnsFound = true;
            $_SESSION['lab_' . $labKey . '_columns'] = true;
        }
        
        // التحقق من استخراج بيانات المستخدمين
        if (preg_match('/users|admin|password|username/i', $productId)) {
            $dataExtracted = true;
            $_SESSION['lab_' . $labKey . '_extracted'] = true;
        }
    }
    
    if (!$unionDetected && isset($products[$productId])) {
        $results = $products[$productId];
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_extracted'])) {
        $page = 'product';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_union'], $_SESSION['lab_' . $labKey . '_columns'], $_SESSION['lab_' . $labKey . '_extracted']);
    }
}

$GLOBALS['lab_title'] = 'UNION Based SQLi';
renderLabHeader();
?>

<div class="lab-header">
    <h1>UNION Based SQL Injection</h1>
    <p>استخراج بيانات من جداول أخرى</p>
    <span class="lab-badge badge-beginner">مبتدئ</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>تختبر أمان متجر <strong>إلكترونيات شارع حدة</strong> الإلكتروني.</p>
                <p>صفحة عرض المنتجات تستخدم معامل ID في الـ URL.</p>
                <p><strong>الهدف:</strong> استخدم UNION لاستخراج بيانات جدول المستخدمين.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة تقنية</h2>
            <p style="color:#aaa;">UNION يسمح بدمج نتائج استعلامين. لكن هناك شروط يجب اكتشافها...</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('shop'); ?>" class="btn btn-primary">دخول المتجر</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة للتحديات</a>
    </div>

<?php elseif ($page === 'shop'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://hadda-electronics.ye</div></div>
        <div class="app-body">
            <h3>📱 إلكترونيات شارع حدة</h3>
            <p style="color:#666;margin-bottom:20px;">أفضل الأسعار في صنعاء</p>
            
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:15px;">
                <?php foreach ($products as $id => $p): ?>
                <a href="<?php echo stepUrl('product', ['pid' => $id]); ?>" style="background:#f8f9fa;padding:15px;border-radius:8px;text-decoration:none;color:#333;text-align:center;">
                    <div style="font-size:2rem;">📱</div>
                    <strong><?php echo $p['name']; ?></strong>
                    <p style="color:#27ae60;margin-top:5px;"><?php echo $p['price']; ?> ر.ي</p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'product'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://hadda-electronics.ye/product?id=<?php echo urlencode($productId); ?></div></div>
        <div class="app-body">
            <h3>📱 تفاصيل المنتج</h3>
            
            <?php if ($dataExtracted || isset($_SESSION['lab_' . $labKey . '_extracted'])): ?>
                <div style="background:#e8f5e9;padding:15px;border-radius:8px;margin-bottom:15px;">
                    <strong>🎉 تم استخراج بيانات المستخدمين!</strong>
                </div>
                <div style="background:#1a1a2e;padding:15px;border-radius:8px;font-family:monospace;color:#0f0;">
                    <table style="width:100%;color:#0f0;">
                        <tr><th style="text-align:left;padding:5px;">Username</th><th style="text-align:left;padding:5px;">Email</th><th style="text-align:left;padding:5px;">Password</th></tr>
                        <tr><td style="padding:5px;">admin</td><td style="padding:5px;">admin@hadda.ye</td><td style="padding:5px;">Sup3rS3cr3t!</td></tr>
                        <tr><td style="padding:5px;">manager</td><td style="padding:5px;">mgr@hadda.ye</td><td style="padding:5px;">M@nager2024</td></tr>
                        <tr><td style="padding:5px;">support</td><td style="padding:5px;">help@hadda.ye</td><td style="padding:5px;">Support123</td></tr>
                    </table>
                </div>
            <?php elseif ($columnsFound || isset($_SESSION['lab_' . $labKey . '_columns'])): ?>
                <div style="background:#fff3cd;padding:15px;border-radius:8px;margin-bottom:15px;">
                    <strong>تقدم جيد!</strong> الآن حاول استخراج بيانات من جداول أخرى.
                </div>
            <?php elseif ($unionDetected): ?>
                <div style="background:#ffebee;padding:15px;border-radius:8px;margin-bottom:15px;color:#c62828;">
                    ⚠️ خطأ في الاستعلام. راجع بنية الـ payload.
                </div>
            <?php elseif (!empty($results)): ?>
                <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                    <h4><?php echo $results['name']; ?></h4>
                    <p style="color:#666;"><?php echo $results['desc']; ?></p>
                    <p style="color:#27ae60;font-size:1.2rem;margin-top:10px;"><strong><?php echo $results['price']; ?> ر.ي</strong></p>
                    <button style="background:#667eea;color:#fff;padding:10px 20px;border:none;border-radius:5px;margin-top:10px;cursor:pointer;">أضف للسلة</button>
                </div>
            <?php else: ?>
                <div style="background:#ffebee;padding:15px;border-radius:8px;color:#c62828;">
                    المنتج غير موجود
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($dataExtracted || isset($_SESSION['lab_' . $labKey . '_extracted'])): ?>
        <div class="alert alert-success">استخرجت بيانات المستخدمين بنجاح!</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('shop'); ?>" class="btn btn-secondary">العودة للمتجر</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color:#bbb;margin-right:20px;line-height:2;">
            <li>UNION يدمج نتائج استعلامين في نتيجة واحدة</li>
            <li>يجب اكتشاف عدد الأعمدة وأنواعها</li>
            <li>يمكن استخراج بيانات من جداول مختلفة</li>
            <li>الحماية: Prepared Statements + Least Privilege</li>
        </ul>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
