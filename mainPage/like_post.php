<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? null;

if (!$post_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

try {
    // Check if like exists
    $stmt = $pdo->prepare("SELECT like_id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post_id, $_SESSION['user_id']]);
    $existing_like = $stmt->fetch();

    if ($existing_like) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $_SESSION['user_id']]);

        // Create notification for post owner
        $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $post_owner = $stmt->fetch();

        if ($post_owner && $post_owner['user_id'] != $_SESSION['user_id']) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'like', ?)");
            $stmt->execute([$post_owner['user_id'], $post_id]);
        }
    }

    // Get updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) as like_count FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetch()['like_count'];

    echo json_encode([
        'success' => true,
        'like_count' => $like_count
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?> 