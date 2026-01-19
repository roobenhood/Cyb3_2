<?php
/**
 * وظائف مساعدة للابات
 * مع نظام فلاج ديناميكي لمنع المشاركة
 */

// بدء الجلسة إذا لم تكن مبدوءة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * البحث عن config.php وتحميله
 */
function findAndLoadConfig() {
    // إذا كان SITE_URL معرفاً، config محمّل بالفعل
    if (defined('SITE_URL')) {
        return true;
    }
    
    // البحث التصاعدي من المجلد الحالي
    $dir = __DIR__;
    for ($i = 0; $i < 10; $i++) {
        $configPath = $dir . '/config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
            return true;
        }
        $parent = dirname($dir);
        if ($parent === $dir) break; // وصلنا للجذر
        $dir = $parent;
    }
    
    // محاولة من SCRIPT_FILENAME
    if (isset($_SERVER['SCRIPT_FILENAME'])) {
        $scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
        for ($i = 0; $i < 10; $i++) {
            $configPath = $scriptDir . '/config.php';
            if (file_exists($configPath)) {
                require_once $configPath;
                return true;
            }
            $parent = dirname($scriptDir);
            if ($parent === $scriptDir) break;
            $scriptDir = $parent;
        }
    }
    
    return false;
}

// تحميل الإعدادات
findAndLoadConfig();

// مفتاح سري لتوليد التوكنات (يجب تغييره في الإنتاج)
if (!defined('FLAG_SECRET_KEY')) {
    define('FLAG_SECRET_KEY', getenv('FLAG_SECRET_KEY') ?: 'AlwaniCTF_S3cr3t_K3y_2024');
}

/**
 * الحصول على رابط العودة للتحديات
 */
function getBackToMainUrl() {
    if (defined('SITE_URL')) {
        return SITE_URL;
    }
    
    // حساب المستوى من المسار الحالي
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $depth = substr_count($scriptPath, 'challenges_data');
    if ($depth > 0) {
        // حساب عدد المجلدات بعد challenges_data
        $afterChallenges = preg_replace('/.*challenges_data/', '', $scriptPath);
        $levels = substr_count($afterChallenges, '/');
        return str_repeat('../', $levels + 1);
    }
    return '../';
}

/**
 * التحقق من تسجيل الدخول
 */
function checkLabLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        $baseUrl = getBackToMainUrl();
        header('Location: ' . $baseUrl . 'login.php');
        exit;
    }
}

/**
 * توليد توكن فريد للمستخدم
 * يعتمد على: user_id + challenge_id + secret_key
 */
function generateUserToken($challengeId, $userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? 0;
    }
    
    // توليد توكن قصير (8 أحرف) من HMAC
    $data = $userId . ':' . $challengeId . ':' . date('Y-m-d');
    $hash = hash_hmac('sha256', $data, FLAG_SECRET_KEY);
    return strtoupper(substr($hash, 0, 8));
}

/**
 * التحقق هل التوكين مفعل في الإعدادات
 */
function isTokenEnabled() {
    global $pdo;
    
    if (!isset($pdo)) {
        return true; // الافتراضي مفعل
    }
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'flag_token_enabled'");
        $stmt->execute();
        $result = $stmt->fetch();
        return !$result || $result['setting_value'] === '1';
    } catch (PDOException $e) {
        return true;
    }
}

/**
 * الحصول على الفلاج الديناميكي للمستخدم
 * الفلاج النهائي = الفلاج الأصلي + _ + توكن المستخدم (إذا كان التوكين مفعل)
 */
function getDynamicFlag($folderName) {
    global $pdo;
    
    if (!isset($_SESSION['user_id']) || !isset($pdo)) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    $tokenEnabled = isTokenEnabled();
    
    try {
        // أولاً: استخدام challenge_id من الـ URL إذا متوفر (أدق)
        $challengeId = $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? null);
        
        if ($challengeId) {
            $stmt = $pdo->prepare("SELECT id, flag FROM challenges WHERE id = ? AND is_active = 1");
            $stmt->execute([$challengeId]);
        } else {
            // fallback: البحث بالمجلد (مطابقة دقيقة)
            $stmt = $pdo->prepare("SELECT id, flag FROM challenges WHERE folder_name = ? AND is_active = 1");
            $stmt->execute([$folderName]);
        }
        
        $challenge = $stmt->fetch();
        
        if (!$challenge || empty($challenge['flag'])) {
            return null;
        }
        
        $baseFlag = $challenge['flag'];
        
        // إذا التوكين غير مفعل، أرجع الفلاج الأصلي
        if (!$tokenEnabled) {
            return $baseFlag;
        }
        
        // توليد الفلاج الديناميكي
        $userToken = generateUserToken($challenge['id'], $userId);
        
        // إضافة التوكن للفلاج
        // مثال: CTF{original_flag} -> CTF{original_flag}_A1B2C3D4
        return $baseFlag . '_' . $userToken;
        
    } catch (PDOException $e) {
        error_log('getDynamicFlag error: ' . $e->getMessage());
        return null;
    }
}

/**
 * التحقق من صحة الفلاج الديناميكي
 * يُستخدم في submit_flag.php
 */
function verifyDynamicFlag($challengeId, $submittedFlag, $userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT flag FROM challenges WHERE id = ?");
        $stmt->execute([$challengeId]);
        $challenge = $stmt->fetch();
        
        if (!$challenge) {
            return false;
        }
        
        $baseFlag = $challenge['flag'];
        $expectedToken = generateUserToken($challengeId, $userId);
        $expectedFlag = $baseFlag . '_' . $expectedToken;
        
        // التحقق: إما الفلاج الأصلي أو الفلاج الديناميكي
        return ($submittedFlag === $baseFlag || $submittedFlag === $expectedFlag);
        
    } catch (PDOException $e) {
        error_log('verifyDynamicFlag error: ' . $e->getMessage());
        return false;
    }
}

/**
 * الحصول على معرف التحدي من المجلد
 */
function getChallengeIdByFolder($folderName) {
    global $pdo;
    
    // أولاً: استخدام challenge_id من الـ URL إذا متوفر
    $challengeId = $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? null);
    if ($challengeId) {
        return $challengeId;
    }
    
    if (!isset($pdo)) {
        return null;
    }
    
    try {
        // مطابقة دقيقة
        $stmt = $pdo->prepare("SELECT id FROM challenges WHERE folder_name = ? AND is_active = 1");
        $stmt->execute([$folderName]);
        $challenge = $stmt->fetch();
        return $challenge ? $challenge['id'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * الحصول على الخطوة الحالية
 */
function getCurrentStep($maxSteps = 5) {
    $step = intval($_GET['step'] ?? 1);
    return max(1, min($maxSteps, $step));
}

/**
 * تسجيل حل اللاب (تسجيل إكمال الخطوات فقط - الحل الفعلي عبر submit_flag.php)
 */
function markLabCompleted($folderName) {
    global $pdo;
    
    if (!isset($_SESSION['user_id']) || !isset($pdo)) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // أولاً: استخدام challenge_id من الـ URL إذا متوفر (أدق)
        $challengeId = $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? null);
        
        if ($challengeId) {
            // التحقق من وجود التحدي
            $stmt = $pdo->prepare("SELECT id FROM challenges WHERE id = ? AND is_active = 1");
            $stmt->execute([$challengeId]);
            $challenge = $stmt->fetch();
        } else {
            // fallback: البحث بالمجلد (مطابقة دقيقة)
            $stmt = $pdo->prepare("SELECT id FROM challenges WHERE folder_name = ? AND is_active = 1");
            $stmt->execute([$folderName]);
            $challenge = $stmt->fetch();
        }
        
        if ($challenge) {
            // تخزين أن المستخدم أكمل خطوات اللاب
            $_SESSION['lab_completed_' . $challenge['id']] = true;
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log('markLabCompleted error: ' . $e->getMessage());
        return false;
    }
}

/**
 * التحقق من إكمال اللاب (إكمال الخطوات)
 */
function isLabCompleted($folderName) {
    global $pdo;
    
    if (!isset($pdo)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM challenges WHERE folder_name LIKE ? AND is_active = 1");
        $stmt->execute(['%' . $folderName . '%']);
        $challenge = $stmt->fetch();
        
        if ($challenge) {
            return isset($_SESSION['lab_completed_' . $challenge['id']]);
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * التحقق من حل اللاب (تم إرسال الفلاج بنجاح)
 */
function isLabSolved($folderName) {
    global $pdo;
    
    // أولاً: تحقق باستخدام challenge_id من الـ URL إذا متوفر
    $challengeId = $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? null);
    if ($challengeId) {
        return isChallengeSolvedById($challengeId);
    }
    
    if (!isset($_SESSION['user_id']) || !isset($pdo)) {
        return false;
    }
    
    try {
        // استخدام مطابقة دقيقة
        $stmt = $pdo->prepare("SELECT c.id FROM challenges c 
            JOIN solves s ON c.id = s.challenge_id 
            WHERE c.folder_name = ? AND s.user_id = ?");
        $stmt->execute([$folderName, $_SESSION['user_id']]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * التحقق من حل التحدي باستخدام ID مباشرة
 */
function isChallengeSolvedById($challengeId) {
    global $pdo;
    
    if (!isset($_SESSION['user_id']) || !isset($pdo) || !$challengeId) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM solves WHERE challenge_id = ? AND user_id = ?");
        $stmt->execute([$challengeId, $_SESSION['user_id']]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * حفظ تقدم الخطوة
 */
function saveStepProgress($labKey, $step) {
    $_SESSION['lab_' . $labKey . '_step_' . $step] = true;
}

/**
 * التحقق من إكمال خطوة
 */
function isStepCompleted($labKey, $step) {
    return isset($_SESSION['lab_' . $labKey . '_step_' . $step]);
}

/**
 * الحصول على قائمة الخطوات المكتملة
 */
function getCompletedSteps($labKey, $totalSteps) {
    $completed = [];
    for ($i = 1; $i <= $totalSteps; $i++) {
        if (isStepCompleted($labKey, $i)) {
            $completed[] = $i;
        }
    }
    return $completed;
}

/**
 * إعادة تعيين تقدم اللاب
 */
function resetLabProgress($labKey, $totalSteps) {
    for ($i = 1; $i <= $totalSteps; $i++) {
        unset($_SESSION['lab_' . $labKey . '_step_' . $i]);
    }
}

/**
 * تنظيف جلسة اللاب عند الدخول
 * يُستدعى في بداية كل لاب لضمان عدم تداخل الجلسات
 */
function initLabSession($labKey) {
    // تخزين معرف التحدي من الـ URL (مهم للتحقق من الحل)
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $_SESSION['current_challenge_id'] = $_GET['id'];
    }
    
    // التحقق من أن هذا لاب جديد
    if (!isset($_SESSION['current_lab_key']) || $_SESSION['current_lab_key'] !== $labKey) {
        // مسح كل متغيرات lab_ ما عدا lab_completed_ و current_challenge_id
        $keysToDelete = [];
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'lab_') === 0 && strpos($key, 'lab_completed_') !== 0) {
                $keysToDelete[] = $key;
            }
        }
        foreach ($keysToDelete as $key) {
            unset($_SESSION[$key]);
        }
        // تعيين اللاب الحالي
        $_SESSION['current_lab_key'] = $labKey;
    }
}

/**
 * مسح كل متغيرات جلسة لاب معين
 */
function clearLabSession($labKey) {
    $prefix = 'lab_' . $labKey;
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, $prefix) === 0) {
            unset($_SESSION[$key]);
        }
    }
}

/**
 * التحقق من نمط في المدخلات
 */
function checkPattern($input, $patterns) {
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            return true;
        }
    }
    return false;
}

/**
 * بيانات يمنية للسيناريوهات
 */
function getYemeniData() {
    return [
        'cities' => ['صنعاء', 'عدن', 'تعز', 'الحديدة', 'إب'],
        'streets' => [
            'شارع الزبيري',
            'شارع تعز',
            'شارع حدة',
            'شارع الستين',
            'شارع الرقاص',
            'شارع المطار',
            'شارع الجزائر'
        ],
        'companies' => [
            'مؤسسة صنعاء للتجارة',
            'شركة اليمن للاتصالات',
            'بنك اليمن والكويت',
            'مستشفى الثورة',
            'جامعة صنعاء'
        ],
        'names' => [
            'أحمد محمد',
            'علي عبدالله',
            'محمد صالح',
            'خالد ناصر',
            'عمر أحمد'
        ],
        'emails' => [
            'admin@sanaa-company.ye',
            'info@yemen-tech.ye',
            'support@sanaa-bank.ye'
        ]
    ];
}

/**
 * إنشاء رابط لصفحة داخل اللاب
 * يستخدم النظام الجديد مع معامل step
 */
function labUrl($step = 'intro', $params = []) {
    $challengeId = $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? null);
    
    if (!$challengeId) {
        // fallback للرابط مع step فقط
        $allParams = array_merge(['step' => $step], $params);
        return '?' . http_build_query($allParams);
    }
    
    $allParams = array_merge(['id' => $challengeId, 'step' => $step], $params);
    return 'challenge_play.php?' . http_build_query($allParams);
}

/**
 * إنشاء رابط step للنظام الموحد (يحتفظ بـ id)
 */
function stepUrl($step, $extraParams = []) {
    $challengeId = $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? null);
    $params = ['step' => $step];
    
    if ($challengeId) {
        $params['id'] = $challengeId;
    }
    
    $params = array_merge($params, $extraParams);
    return '?' . http_build_query($params);
}

/**
 * إنشاء رابط العودة للتحدي (صفحة إرسال الفلاج)
 */
function challengesUrl() {
    $challengeId = $_SESSION['current_challenge_id'] ?? ($_GET['id'] ?? null);
    $baseUrl = defined('SITE_URL') ? SITE_URL : '';
    
    // إذا كان لدينا معرف التحدي، نعود لصفحة إرسال الفلاج
    if ($challengeId) {
        return $baseUrl . '/challenge_view.php?id=' . $challengeId;
    }
    
    // fallback: العودة لقائمة التحديات
    return $baseUrl . '/challenges.php';
}

/**
 * إنشاء رابط لعرض التحدي (إرسال الفلاج)
 */
function challengeViewUrl($folderName) {
    $challengeId = getChallengeIdByFolder($folderName);
    $baseUrl = defined('SITE_URL') ? SITE_URL : '';
    return $baseUrl . '/challenge_view.php?id=' . ($challengeId ?: '');
}
?>
