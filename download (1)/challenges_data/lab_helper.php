<?php
/**
 * Lab Helper - مساعد اللابات
 * يوفر وظائف مشتركة لتسجيل الحلول في قاعدة البيانات
 */

// تحميل الإعدادات الرئيسية - البحث التصاعدي
$configLoaded = false;
$dir = __DIR__;
for ($i = 0; $i < 5; $i++) {
    $configPath = $dir . '/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
        break;
    }
    $dir = dirname($dir);
}

if (!$configLoaded) {
    // محاولة أخيرة
    $parentConfig = dirname(__DIR__) . '/config.php';
    if (file_exists($parentConfig)) {
        require_once $parentConfig;
    }
}

/**
 * تسجيل حل مستوى في اللاب
 * 
 * @param string $labType نوع اللاب (xss, sqli, file_upload, path_traversal)
 * @param int $levelNumber رقم المستوى
 * @param string $flag الفلاج
 * @param int $points النقاط (افتراضي 100)
 * @return array نتيجة العملية
 */
function registerLabSolve($labType, $levelNumber, $flag, $points = 100) {
    global $pdo;
    
    $result = [
        'success' => false,
        'message' => '',
        'points_earned' => 0,
        'is_first_blood' => false,
        'already_solved' => false
    ];
    
    // التحقق من تسجيل الدخول
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        $result['message'] = 'يجب تسجيل الدخول لحفظ تقدمك';
        return $result;
    }
    
    $userId = $_SESSION['user_id'];
    
    try {
        // البحث عن التحدي في قاعدة البيانات
        // نبحث عن تحدي يطابق اسم المجلد (مثل xss/level1)
        $folderName = $labType . '/level' . $levelNumber;
        
        $stmt = $pdo->prepare("SELECT id, points, bonus_enabled, bonus_count, bonus_points FROM challenges WHERE folder_name = ? AND is_active = 1");
        $stmt->execute([$folderName]);
        $challenge = $stmt->fetch();
        
        // إذا لم يوجد التحدي، نحاول البحث بطريقة أخرى
        if (!$challenge) {
            $folderName = $labType . '_level' . $levelNumber;
            $stmt->execute([$folderName]);
            $challenge = $stmt->fetch();
        }
        
        if (!$challenge) {
            // التحدي غير موجود في قاعدة البيانات
            // نسجل في الجلسة فقط كحل مؤقت
            $result['message'] = 'التحدي غير مسجل في قاعدة البيانات - تم حفظ التقدم محلياً';
            return $result;
        }
        
        $challengeId = $challenge['id'];
        $challengePoints = $challenge['points'] ?: $points;
        
        // التحقق هل تم حله مسبقاً
        $stmt = $pdo->prepare("SELECT id FROM solves WHERE user_id = ? AND challenge_id = ?");
        $stmt->execute([$userId, $challengeId]);
        
        if ($stmt->fetch()) {
            $result['already_solved'] = true;
            $result['message'] = 'لقد حللت هذا التحدي مسبقاً';
            return $result;
        }
        
        // التحقق من First Blood
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM solves WHERE challenge_id = ?");
        $stmt->execute([$challengeId]);
        $solveCount = $stmt->fetchColumn();
        $isFirstBlood = ($solveCount == 0);
        
        // حساب النقاط (مع المكافآت إن وجدت)
        $pointsEarned = $challengePoints;
        
        if ($challenge['bonus_enabled'] && $challenge['bonus_count'] > 0) {
            $bonusPoints = json_decode($challenge['bonus_points'], true);
            $position = $solveCount + 1;
            
            if ($bonusPoints && isset($bonusPoints[$position])) {
                $pointsEarned += $bonusPoints[$position];
            }
        }
        
        // تسجيل الحل
        $stmt = $pdo->prepare("
            INSERT INTO solves (user_id, challenge_id, points_earned, is_first_blood) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $challengeId, $pointsEarned, $isFirstBlood ? 1 : 0]);
        
        // تحديث نقاط المستخدم
        $stmt = $pdo->prepare("UPDATE users SET score = score + ? WHERE id = ?");
        $stmt->execute([$pointsEarned, $userId]);
        
        // تحديث عدد الحلول في التحدي
        $stmt = $pdo->prepare("UPDATE challenges SET solves_count = solves_count + 1 WHERE id = ?");
        $stmt->execute([$challengeId]);
        
        // تسجيل النشاط
        if (function_exists('logActivity')) {
            logActivity('challenge_solved', "Solved {$labType} level {$levelNumber}", $userId);
        }
        
        $result['success'] = true;
        $result['points_earned'] = $pointsEarned;
        $result['is_first_blood'] = $isFirstBlood;
        $result['message'] = 'تم تسجيل الحل بنجاح!';
        
    } catch (PDOException $e) {
        error_log('Lab solve error: ' . $e->getMessage());
        $result['message'] = 'حدث خطأ في تسجيل الحل';
    }
    
    return $result;
}

/**
 * الحصول على تقدم المستخدم في لاب معين
 */
function getLabProgress($labType) {
    global $pdo;
    
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        return [];
    }
    
    $userId = $_SESSION['user_id'];
    $solvedLevels = [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.folder_name 
            FROM solves s
            JOIN challenges c ON s.challenge_id = c.id
            WHERE s.user_id = ? AND c.folder_name LIKE ?
        ");
        $stmt->execute([$userId, $labType . '%']);
        
        while ($row = $stmt->fetch()) {
            // استخراج رقم المستوى من اسم المجلد
            if (preg_match('/level(\d+)/', $row['folder_name'], $matches)) {
                $solvedLevels[] = (int)$matches[1];
            }
        }
    } catch (PDOException $e) {
        error_log('Get lab progress error: ' . $e->getMessage());
    }
    
    return $solvedLevels;
}
