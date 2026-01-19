<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = intval($data['notification_id'] ?? 0);

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
$result = $stmt->execute([$notification_id]);

echo json_encode(['success' => $result]);