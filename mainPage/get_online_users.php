<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['users' => []]);
    exit();
}

try {
    // Get users who were active in the last 5 minutes
    $stmt = $pdo->prepare("
        SELECT user_id, username, profile_picture 
        FROM users 
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND user_id != ?
        ORDER BY last_activity DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['users' => $online_users]);
} catch (PDOException $e) {
    echo json_encode(['users' => []]);
}
?> 