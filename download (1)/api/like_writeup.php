<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$writeup_id = intval($data['writeup_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$writeup_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid writeup']);
    exit;
}

// التحقق من وجود إعجاب سابق
$stmt = $pdo->prepare("SELECT id FROM writeup_likes WHERE writeup_id = ? AND user_id = ?");
$stmt->execute([$writeup_id, $user_id]);
$existing = $stmt->fetch();

if ($existing) {
    // إزالة الإعجاب
    $stmt = $pdo->prepare("DELETE FROM writeup_likes WHERE writeup_id = ? AND user_id = ?");
    $stmt->execute([$writeup_id, $user_id]);
    
    $stmt = $pdo->prepare("UPDATE writeups SET likes_count = likes_count - 1 WHERE id = ?");
    $stmt->execute([$writeup_id]);
    
    $liked = false;
} else {
    // إضافة إعجاب
    $stmt = $pdo->prepare("INSERT INTO writeup_likes (writeup_id, user_id) VALUES (?, ?)");
    $stmt->execute([$writeup_id, $user_id]);
    
    $stmt = $pdo->prepare("UPDATE writeups SET likes_count = likes_count + 1 WHERE id = ?");
    $stmt->execute([$writeup_id]);
    
    $liked = true;
}

// جلب العدد الجديد
$stmt = $pdo->prepare("SELECT likes_count FROM writeups WHERE id = ?");
$stmt->execute([$writeup_id]);
$likes = $stmt->fetchColumn();

echo json_encode(['success' => true, 'liked' => $liked, 'likes' => $likes]);