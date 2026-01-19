<?php
require_once 'config.php';

header('Content-Type: application/json');

// تحميل وظائف الفلاج الديناميكي
require_once __DIR__ . '/challenges_data/shared/lab_helper.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => __('login_to_solve')]);
    exit();
}

// التحقق من وضع المسابقة
$competition_enabled = getSetting('competition_enabled', '0');
if ($competition_enabled === '1') {
    $start = getSetting('competition_start', '');
    $end = getSetting('competition_end', '');
    $now = time();
    
    if ($start && strtotime($start) > $now) {
        echo json_encode(['success' => false, 'message' => __('competition_not_started')]);
        exit();
    }
    
    if ($end && strtotime($end) < $now) {
        echo json_encode(['success' => false, 'message' => __('competition_ended')]);
        exit();
    }
}

// التحقق من الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => __('error')]);
    exit();
}

$challenge_id = intval($_POST['challenge_id'] ?? 0);
$submitted_flag = trim($_POST['flag'] ?? '');
$user_id = $_SESSION['user_id'];
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!$challenge_id || empty($submitted_flag)) {
    echo json_encode(['success' => false, 'message' => __('fill_all_fields')]);
    exit();
}

// جلب التحدي
$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ? AND is_active = 1");
$stmt->execute([$challenge_id]);
$challenge = $stmt->fetch();

if (!$challenge) {
    echo json_encode(['success' => false, 'message' => 'التحدي غير موجود']);
    exit();
}

// التحقق من عدم الحل مسبقاً
$stmt = $pdo->prepare("SELECT id FROM solves WHERE user_id = ? AND challenge_id = ?");
$stmt->execute([$user_id, $challenge_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => __('already_solved')]);
    exit();
}

// التحقق من الحد الأقصى للمحاولات
if ($challenge['max_attempts'] > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE user_id = ? AND challenge_id = ?");
    $stmt->execute([$user_id, $challenge_id]);
    $attempts = $stmt->fetchColumn();
    
    if ($attempts >= $challenge['max_attempts']) {
        echo json_encode(['success' => false, 'message' => 'لقد وصلت للحد الأقصى من المحاولات']);
        exit();
    }
}

// === التحقق من الفلاج (يدعم الفلاج الديناميكي) ===
$baseFlag = $challenge['flag'];
$tokenEnabled = isTokenEnabled(); // التحقق من إعداد التوكين

$is_correct = false;

if ($tokenEnabled) {
    // وضع التوكين مفعل: قبول الفلاج الديناميكي أو الأصلي
    $expectedToken = generateUserToken($challenge_id, $user_id);
    $dynamicFlag = $baseFlag . '_' . $expectedToken;
    $is_correct = ($submitted_flag === $baseFlag || $submitted_flag === $dynamicFlag);
} else {
    // وضع التوكين غير مفعل: قبول الفلاج الأصلي فقط
    $is_correct = ($submitted_flag === $baseFlag);
}

// التحقق إذا كان الفلاج صحيح لكن لمستخدم آخر (محاولة غش) - فقط إذا التوكين مفعل
$isCheatAttempt = false;
if ($tokenEnabled && !$is_correct && preg_match('/^' . preg_quote($baseFlag, '/') . '_[A-F0-9]{8}$/i', $submitted_flag)) {
    // الفلاج بصيغة صحيحة لكن التوكن خاطئ = محاولة استخدام فلاج شخص آخر
    $isCheatAttempt = true;
    logActivity('cheat_attempt', "User tried using someone else's flag for challenge ID: $challenge_id");
}

// تسجيل المحاولة
$stmt = $pdo->prepare("INSERT INTO submissions (user_id, challenge_id, submitted_flag, is_correct, ip_address) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$user_id, $challenge_id, $submitted_flag, $is_correct, $ip_address]);

if ($isCheatAttempt) {
    echo json_encode(['success' => false, 'message' => 'هذا الفلاج خاص بمستخدم آخر! يجب حل التحدي بنفسك.']);
    exit();
}

if ($is_correct) {
    // حساب ترتيب الحل
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM solves WHERE challenge_id = ?");
    $stmt->execute([$challenge_id]);
    $solve_position = $stmt->fetchColumn() + 1;
    
    $is_first_blood = ($solve_position == 1);
    
    // حساب النقاط
    $points = $challenge['points'];
    $bonus_earned = 0;
    
    // === Dynamic Scoring ===
    if (($challenge['dynamic_scoring'] ?? 0) == 1) {
        $initial = $challenge['initial_points'] ?? $points;
        $minimum = $challenge['minimum_points'] ?? 50;
        $decay = $challenge['decay_rate'] ?? 10;
        $solves = $challenge['solves_count'] ?? 0;
        
        $points = max($minimum, $initial - ($decay * $solves));
        $points = intval($points);
    }
    
    // === First Blood Bonus ===
    if ($is_first_blood && ($challenge['first_blood_bonus'] ?? 0) > 0) {
        $bonus_earned += $challenge['first_blood_bonus'];
    }
    
    // === نظام المكافآت المتقدم ===
    $bonus_enabled = (($challenge['bonus_enabled'] ?? 0) == 1);
    $bonus_count = intval($challenge['bonus_count'] ?? 0);
    $bonus_points = json_decode($challenge['bonus_points'] ?? '{}', true) ?: [];
    
    if ($bonus_enabled && $bonus_count > 0 && $solve_position <= $bonus_count) {
        $bonus_earned += intval($bonus_points[$solve_position] ?? 0);
    }
    
    $total_points = $points + $bonus_earned;
    
    // تسجيل الحل
    $stmt = $pdo->prepare("INSERT INTO solves (user_id, challenge_id, points_earned, is_first_blood) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $challenge_id, $total_points, $is_first_blood ? 1 : 0]);
    
    // تحديث نقاط المستخدم
    $stmt = $pdo->prepare("UPDATE users SET score = score + ? WHERE id = ?");
    $stmt->execute([$total_points, $user_id]);
    
    // تحديث عدد الحلول للتحدي
    $stmt = $pdo->prepare("UPDATE challenges SET solves_count = solves_count + 1 WHERE id = ?");
    $stmt->execute([$challenge_id]);
    
    // تحديث نقاط الفريق
    $stmt = $pdo->prepare("SELECT team_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['team_id']) {
        $stmt = $pdo->prepare("UPDATE teams SET score = score + ? WHERE id = ?");
        $stmt->execute([$total_points, $user['team_id']]);
    }
    
    // تسجيل النشاط
    logActivity('solve_challenge', "Solved challenge ID: $challenge_id, Points: $total_points");
    
    $message = __('flag_correct');
    if ($is_first_blood) {
        $message .= ' 🩸 ' . __('first_blood');
    } elseif ($bonus_earned > 0) {
        $message .= ' 🎯 +' . $bonus_earned . ' ' . __('bonus');
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'points' => $total_points,
        'base_points' => $points,
        'bonus' => $bonus_earned,
        'position' => $solve_position,
        'first_blood' => $is_first_blood
    ]);
} else {
    logActivity('wrong_flag', "Wrong flag for challenge ID: $challenge_id");
    echo json_encode(['success' => false, 'message' => __('flag_incorrect')]);
}