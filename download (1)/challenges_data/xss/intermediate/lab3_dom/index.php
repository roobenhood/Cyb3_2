<?php
/**
 * XSS Lab 3 - DOM Based XSS
 * المستوى: متوسط
 */
ob_start();
require_once __DIR__ . '/../../../shared/lab_helper.php';
require_once __DIR__ . '/../../../shared/lab_styles.php';
checkLabLogin();

$labKey = 'xss_lab3_dom';
$folderName = 'xss/intermediate/lab3_dom';
initLabSession($labKey);

$page = $_GET['step'] ?? 'intro';
$solved = isLabSolved($folderName);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exploited'])) {
    $_SESSION['lab_' . $labKey . '_dom_exploited'] = true;
    header('Location: ' . stepUrl('complete'));
    exit;
}

if ($page === 'complete') {
    markLabCompleted($folderName);
}

$GLOBALS['lab_title'] = 'DOM-Based XSS';
renderLabHeader();
?>

<div class="lab-header">
    <h1>DOM-Based XSS</h1>
    <p>اكتشاف ثغرة في كود JavaScript</p>
    <span class="lab-badge badge-intermediate">متوسط</span>
</div>

<?php if ($page === 'intro'): ?>
    <?php if ($solved): ?>
        <?php renderSuccessBox($folderName); ?>
    <?php else: ?>
        <div class="lab-card">
            <h2>السيناريو</h2>
            <div class="scenario-box">
                <p>أنت تفحص تطبيق <strong>بنك صنعاء الإلكتروني</strong>.</p>
                <p>لاحظت أن صفحة سجل العمليات تستخدم JavaScript لمعالجة بعض البيانات من الـ URL.</p>
                <p><strong>الهدف:</strong> افحص كود JavaScript وابحث عن طريقة لحقن كود خبيث.</p>
            </div>
        </div>
        <div class="lab-card">
            <h2>معلومة تقنية</h2>
            <p style="color:#aaa;">هذا النوع يختلف عن XSS التقليدي - الثغرة تحدث في كود العميل وليس الخادم.</p>
        </div>
        <div class="text-center mt-20">
            <a href="<?php echo stepUrl('dashboard'); ?>" class="btn btn-primary">دخول التطبيق</a>
        </div>
    <?php endif; ?>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'dashboard'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url">https://sanaa-bank.ye/dashboard</div></div>
        <div class="app-body">
            <h3 style="margin: 0;">💳 لوحة التحكم</h3>
            <div style="background: #e8f5e9; padding:10px; border-radius:5px; margin:15px 0;">
                مرحباً <strong>محمد أحمد</strong> | آخر دخول: اليوم 10:30 ص
            </div>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 15px 0;">
                <p style="margin: 0;"><strong>الرصيد المتاح:</strong> <span style="color:#27ae60;font-size:1.2rem;">1,250,000 ر.ي</span></p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center; opacity: 0.5; cursor:not-allowed;">
                    <div style="font-size: 1.5rem;">💸</div><strong>تحويل أموال</strong>
                    <small style="display:block;color:#666;">غير متاح في الاختبار</small>
                </div>
                <a href="<?php echo stepUrl('history'); ?>" style="background: #f3e5f5; padding: 20px; border-radius: 8px; text-decoration: none; color: #7b1fa2; text-align: center;">
                    <div style="font-size: 1.5rem;">📜</div><strong>سجل العمليات</strong>
                </a>
            </div>
        </div>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('intro'); ?>" class="btn btn-secondary">العودة</a>
        <a href="<?php echo stepUrl('history'); ?>" class="btn btn-primary">سجل العمليات</a>
    </div>

<?php elseif ($page === 'history'): ?>
    <div class="vuln-app">
        <div class="app-bar"><span>🔒</span><div class="app-url" id="current-url">https://sanaa-bank.ye/history#filter=all</div></div>
        <div class="app-body">
            <h3 style="margin: 0;">📜 سجل العمليات</h3>
            <div style="margin: 15px 0;">
                <label style="color: #666;">فلترة حسب النوع:</label>
                <select onchange="updateFilter(this.value)" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd;">
                    <option value="all">جميع العمليات</option>
                    <option value="incoming">الواردات</option>
                    <option value="outgoing">الصادرات</option>
                </select>
            </div>
            <div id="filter-display" style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px;"></div>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; text-align: right;">التاريخ</th>
                    <th style="padding: 10px; text-align: right;">الوصف</th>
                    <th style="padding: 10px; text-align: right;">المبلغ</th>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">2024-01-15</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">تحويل وارد - شركة المقاولات</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; color: #27ae60;">+150,000</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">2024-01-14</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">سحب - صراف آلي</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; color: #e53935;">-20,000</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">2024-01-12</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee;">إيداع نقدي</td>
                    <td style="padding: 10px; border-bottom: 1px solid #eee; color: #27ae60;">+500,000</td>
                </tr>
            </table>
        </div>
    </div>
    
    <script>
    function updateFilter(value) {
        window.location.hash = 'filter=' + value;
        displayFilter();
    }
    function displayFilter() {
        var hash = window.location.hash.substring(1);
        if (hash) {
            var filterValue = hash.split('=')[1];
    // نقطة الضعف موجودة هنا - ابحث عنها
            document.getElementById('filter-display').innerHTML = 'عرض: <strong>' + decodeURIComponent(filterValue) + '</strong>';
            document.getElementById('current-url').textContent = 'https://sanaa-bank.ye/history#' + hash;
        }
    }
    window.onload = displayFilter;
    window.onhashchange = displayFilter;
    
    // كاشف الاستغلال
    var originalAlert = window.alert;
    window.alert = function(msg) {
        originalAlert(msg);
        document.getElementById('exploit-form').submit();
    };
    </script>
    
    <form method="POST" id="exploit-form" style="display: none;">
        <input type="hidden" name="exploited" value="1">
    </form>
    
    <div class="lab-card">
        <h2>ملاحظة</h2>
        <p style="color:#aaa;">افحص سلوك الصفحة عند تغيير الـ URL. قد تجد شيئاً مثيراً للاهتمام في مصدر الصفحة.</p>
    </div>
    
    <div class="nav-buttons">
        <a href="<?php echo stepUrl('dashboard'); ?>" class="btn btn-secondary">العودة</a>
    </div>

<?php elseif ($page === 'complete'): ?>
    <?php renderSuccessBox($folderName); ?>
    <div class="lab-card">
        <h2>ما تعلمته</h2>
        <ul style="color: #bbb; margin-right: 20px; line-height: 2;">
            <li>بعض الثغرات لا تظهر في استجابة الخادم</li>
            <li>فحص كود JavaScript ضروري لاكتشاف هذا النوع</li>
            <li>الحماية تتطلب معالجة آمنة للبيانات في الكود</li>
        </ul>
    </div>
    <div class="nav-buttons">
        <a href="<?php echo challengesUrl(); ?>" class="btn btn-primary">العودة للتحديات</a>
    </div>
<?php endif; ?>

<?php renderLabFooter(); ?>
