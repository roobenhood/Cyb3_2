<?php
/**
 * XSS Lab 5 - Cookie Theft
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'xss_lab5_cookie';
$folderName = 'xss/intermediate/lab5_cookie';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

// جلسة المدير
$_SESSION['lab_admin_session'] = $_SESSION['lab_admin_session'] ?? 'sess_' . bin2hex(random_bytes(16));

if (!isset($_SESSION['lab_' . $labKey . '_reviews'])) {
    $_SESSION['lab_' . $labKey . '_reviews'] = [
        ['user' => 'م. سارة أحمد', 'rating' => 5, 'text' => 'خدمة ممتازة وتوصيل سريع!', 'date' => '2024-01-15'],
        ['user' => 'علي محمد', 'rating' => 4, 'text' => 'منتجات أصلية وأسعار معقولة', 'date' => '2024-01-14'],
        ['user' => 'خالد ناصر', 'rating' => 5, 'text' => 'أفضل متجر في صنعاء', 'date' => '2024-01-13'],
    ];
}

$reviewAdded = false;
if ($page === 'reviews' && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['review'])) {
    $review = $_POST['review'];
    if (preg_match('/document\.cookie|\.cookie/i', $review)) {
        $_SESSION['lab_' . $labKey . '_cookie_attempt'] = true;
    }
    $_SESSION['lab_' . $labKey . '_reviews'][] = [
        'user' => 'زائر', 
        'rating' => intval($_POST['rating'] ?? 5), 
        'text' => $review,
        'date' => date('Y-m-d')
    ];
    $reviewAdded = true;
}

$stolenCookie = null;
if ($page === 'admin_visit') {
    if (!isset($_SESSION['lab_' . $labKey . '_cookie_attempt'])) {
        $page = 'reviews';
    } else {
        foreach ($_SESSION['lab_' . $labKey . '_reviews'] as $review) {
            if (preg_match('/document\.cookie/i', $review['text'])) {
                $stolenCookie = $_SESSION['lab_admin_session'];
                $_SESSION['lab_' . $labKey . '_stolen'] = true;
                break;
            }
        }
    }
}

if ($page === 'complete') {
    if (!isset($_SESSION['lab_' . $labKey . '_stolen'])) {
        $page = 'admin_visit';
    } else {
        markLabCompleted($folderName);
        unset($_SESSION['lab_' . $labKey . '_reviews'], $_SESSION['lab_' . $labKey . '_cookie_attempt'], $_SESSION['lab_' . $labKey . '_stolen']);
    }
}

$GLOBALS['lab_title'] = 'Cookie Theft via XSS';
renderLabHeader();
?>

<div class="lab-header">
    <h1>Cookie Theft via XSS</h1>
    <p>استغلال XSS لسرقة جلسات المستخدمين</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>اكتشفت ثغرة Stored XSS في موقع <strong>صنعاء مول</strong> للتسوق.</p>
                <p>مدير الموقع يراجع تقييمات العملاء يومياً من لوحة التحكم.</p>
                <p><strong>الهدف:</strong> اكتب تقييماً يسرق كوكي جلسة المدير عند مشاهدته.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة</h2>
            <p style="color:#aaa;">في الهجمات الحقيقية، يتم إرسال الكوكي المسروق لسيرفر المهاجم. هنا سنحاكي ذلك.</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('shop'); ?>" class="btn btn-primary">دخول المتجر</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'shop'): ?>
    <script>document.cookie = "admin_session=<?php echo $_SESSION['lab_admin_session']; ?>; path=/";</script>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-mall.ye</div></div>
        <div class="app-body">
            <h3>🛒 صنعاء مول - أكبر مركز تسوق إلكتروني</h3>
            <p style="color:#666;margin-bottom:20px;">شارع الستين - صنعاء</p>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0;">
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem;">👔</div><p>ملابس</p>
                    <small style="color:#27ae60;">خصم 20%</small>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem;">📱</div><p>إلكترونيات</p>
                    <small style="color:#27ae60;">جديد</small>
                </div>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 2rem;">🎮</div><p>ألعاب</p>
                </div>
            </div>
            <a href="<?php echo stepUrl('reviews'); ?>" style="display: block; background: #e3f2fd; padding: 15px; border-radius: 8px; text-decoration: none; color: #1976d2; text-align: center;">
                ⭐ تقييمات العملاء (<?php echo count($_SESSION['lab_' . $labKey . '_reviews']); ?>)
            </a>
        </div>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'reviews'): ?>
    <script>document.cookie = "admin_session=<?php echo $_SESSION['lab_admin_session']; ?>; path=/";</script>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-mall.ye/reviews</div></div>
        <div class="app-body">
            <h3>⭐ تقييمات العملاء</h3>
            
            <div style="max-height:300px;overflow-y:auto;margin-bottom:20px;">
                <?php foreach ($_SESSION['lab_' . $labKey . '_reviews'] as $review): ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <strong><?php echo htmlspecialchars($review['user']); ?></strong>
                            <span style="color:#999;font-size:0.85rem;"><?php echo $review['date']; ?></span>
                        </div>
                        <div style="color:#f9a825;"><?php echo str_repeat('⭐', $review['rating']); ?></div>
                        <p style="margin: 8px 0 0 0;"><?php echo $review['text']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" action="<?php echo stepUrl('reviews'); ?>" class="app-form" style="border-top:1px solid #eee;padding-top:15px;">
                <div style="margin-bottom:10px;">
                    <label style="color:#666;">التقييم:</label>
                    <select name="rating" style="padding: 8px; border-radius: 5px; margin-right:10px;">
                        <option value="5">⭐⭐⭐⭐⭐ ممتاز</option>
                        <option value="4">⭐⭐⭐⭐ جيد جداً</option>
                        <option value="3">⭐⭐⭐ متوسط</option>
                        <option value="2">⭐⭐ ضعيف</option>
                    </select>
                </div>
                <textarea name="review" placeholder="شاركنا تجربتك مع المتجر..." rows="3"></textarea>
                <button type="submit">نشر التقييم</button>
            </form>
        </div>
    </div>
    
    <?php if ($reviewAdded && !isset($_SESSION['lab_' . $labKey . '_cookie_attempt'])): ?>
        <div class="alert alert-info">✓ تم نشر تقييمك بنجاح! يظهر الآن في قائمة التقييمات أعلاه.</div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['lab_' . $labKey . '_cookie_attempt'])): ?>
        <div class="alert alert-success">تم نشر التقييم! المدير سيراجعه قريباً...</div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('admin_visit'); ?>" class="btn btn-primary">محاكاة مراجعة المدير</a>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('shop'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'admin_visit'): ?>
    <div class="lab-card" style="background:#2a2a4a;">
        <h2 style="color:#ff9800;">👤 محاكاة: المدير يراجع التقييمات</h2>
        <p style="color:#aaa;">المدير "أحمد الحسني" يفتح لوحة التحكم لمراجعة التقييمات الجديدة...</p>
    </div>
    
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-mall.ye/admin/reviews</div></div>
        <div class="app-body">
            <div style="background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                🔐 مسجل كـ: <strong>أحمد الحسني</strong> (مدير الموقع)
            </div>
            <h3>تقييمات تحتاج مراجعة</h3>
            <?php foreach ($_SESSION['lab_' . $labKey . '_reviews'] as $review): ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <strong><?php echo htmlspecialchars($review['user']); ?></strong>
                        <span style="color:#f9a825;"><?php echo str_repeat('⭐', $review['rating']); ?></span>
                    </div>
                    <p style="margin-top:8px;"><?php echo $review['text']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if ($stolenCookie): ?>
        <div class="alert alert-success">تم تنفيذ الهجوم وسرقة الكوكي!</div>
        <div class="lab-card">
            <h2>🔓 الكوكي المسروق</h2>
            <div style="background:#1a1a2e;padding:15px;border-radius:8px;font-family:monospace;color:#0f0;word-break:break-all;">
                admin_session=<?php echo $stolenCookie; ?>
            </div>
            <p style="color:#aaa;margin-top:15px;">الآن يمكنك استخدام هذا الكوكي للدخول كمدير!</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('complete'); ?>" class="btn btn-primary">إكمال التحدي</a>
        </div>
    <?php else: ?>
        <div class="lab-card">
            <p style="color:#ff9800;">الـ payload لم ينجح في سرقة الكوكي. تأكد من استخدام document.cookie</p>
            <div class="text-center mt-20">
                <a href="<?php echo stepUrl('reviews'); ?>" class="btn btn-secondary">حاول مرة أخرى</a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('reviews'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color: #bbb; line-height: 2;">
            <li>XSS يمكن استخدامه لسرقة الكوكيز وانتحال هوية المستخدمين</li>
            <li>الكوكي المسروق يمكن استخدامه للدخول بدون كلمة مرور</li>
            <li>الحماية: HttpOnly flag يمنع JavaScript من قراءة الكوكي</li>
            <li>الحماية الإضافية: Secure flag, SameSite attribute</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
