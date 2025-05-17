<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$other_user_id = $_GET['user'];

try {
    // Get messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.profile_picture
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([
        $_SESSION['user_id'], $other_user_id,
        $other_user_id, $_SESSION['user_id']
    ]);
    $messages = $stmt->fetchAll();

    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = TRUE 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
    ");
    $stmt->execute([$other_user_id, $_SESSION['user_id']]);

    // Generate HTML for messages
    $html = '';
    foreach ($messages as $message) {
        $html .= sprintf(
            '<div class="message %s">
                <div class="message-content">%s</div>
                <span class="message-time">%s</span>
            </div>',
            $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received',
            nl2br(htmlspecialchars($message['content'])),
            date('g:i a', strtotime($message['created_at']))
        );
    }

    echo json_encode(['messages' => $html]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 