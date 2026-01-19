<?php
require_once 'config.php';

// ✅ حماية الصفحة - يجب تسجيل الدخول للوصول
requireLogin();

$lang = getCurrentLanguage();
$t = loadLanguage($lang);

// التحقق من وجود معرف التحدي
$challenge_id = intval($_GET['id'] ?? 0);
$page = $_GET['page'] ?? 'index';

// تنظيف اسم الصفحة - منع path traversal
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
if (empty($page)) {
    $page = 'index';
}

if ($challenge_id <= 0) {
    flashMessage('error', $t['invalid_challenge'] ?? 'معرف التحدي غير صحيح');
    header('Location: challenges.php');
    exit;
}

// جلب بيانات التحدي
$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ? AND is_active = 1");
$stmt->execute([$challenge_id]);
$challenge = $stmt->fetch();

if (!$challenge) {
    flashMessage('error', $t['challenge_not_found'] ?? 'التحدي غير موجود');
    header('Location: challenges.php');
    exit;
}

// التحقق من وجود المجلد وملف index.php
$folder_name = $challenge['folder_name'];

// إذا لم يكن هناك مجلد محدد، عرض رسالة
if (empty($folder_name)) {
    $pageTitle = $t['challenge'] ?? 'التحدي';
    include 'includes/header.php';
    ?>
    <div class="container">
        <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🔧</div>
            <h2 style="color: var(--neon-orange); margin-bottom: 15px;"><?php echo $t['challenge_not_ready'] ?? 'التحدي غير جاهز بعد'; ?></h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;"><?php echo $t['challenge_coming_soon'] ?? 'هذا التحدي قيد الإعداد وسيكون متاحاً قريباً.'; ?></p>
            <a href="challenges.php" class="btn btn-neon">← <?php echo $t['back_to_challenges'] ?? 'العودة للتحديات'; ?></a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

$folder_path = __DIR__ . '/challenges_data/' . $folder_name;
$index_file = $folder_path . '/index.php';

// النظام الجديد: كل اللابات في index.php واحد
// إذا كانت الصفحة المطلوبة ليست index، نحولها لـ step parameter
if (!is_dir($folder_path) || !file_exists($index_file)) {
    $pageTitle = $t['challenge'] ?? 'التحدي';
    include 'includes/header.php';
    ?>
    <div class="container">
        <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 4rem; margin-bottom: 20px;">📁</div>
            <h2 style="color: var(--neon-orange); margin-bottom: 15px;"><?php echo $t['challenge_files_missing'] ?? 'ملفات التحدي غير موجودة'; ?></h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;"><?php echo $t['contact_admin'] ?? 'يرجى التواصل مع الإدارة.'; ?></p>
            <a href="challenges.php" class="btn btn-neon">← <?php echo $t['back_to_challenges'] ?? 'العودة للتحديات'; ?></a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// تخزين معرف التحدي في الجلسة للاستخدام في صفحات اللاب
$_SESSION['current_challenge_id'] = $challenge_id;

// تحويل page إلى step للنظام الجديد
// إذا كانت الصفحة ليست index، نضيفها كـ step
if ($page !== 'index' && !isset($_GET['step'])) {
    $_GET['step'] = $page;
}

// تضمين ملف index.php مباشرة
chdir($folder_path);
include $index_file;
?>
