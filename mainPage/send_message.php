<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../SignIn-SignUp-Form/signin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = $_POST['receiver_id'] ?? null;
    $content = trim($_POST['content'] ?? '');

    if ($receiver_id && $content) {
        try {
            // Insert message
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $receiver_id, $content]);

            // Create notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'message', ?)");
            $stmt->execute([$receiver_id, $pdo->lastInsertId()]);

            header('Location: messages.php?user=' . $receiver_id);
            exit();
        } catch (PDOException $e) {
            // Handle error
            header('Location: messages.php?error=1');
            exit();
        }
    }
}

header('Location: messages.php');
exit();
?> 