<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    echo json_encode(['count' => (int)$result['count']]);
} catch (PDOException $e) {
    echo json_encode(['count' => 0]);
}
?> 