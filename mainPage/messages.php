<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../SignIn-SignUp-Form/signin.php');
    exit();
}

// Get all conversations
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        u.username,
        u.profile_picture,
        (
            SELECT content 
            FROM messages 
            WHERE (sender_id = ? AND receiver_id = other_user_id)
               OR (sender_id = other_user_id AND receiver_id = ?)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT created_at 
            FROM messages 
            WHERE (sender_id = ? AND receiver_id = other_user_id)
               OR (sender_id = other_user_id AND receiver_id = ?)
            ORDER BY created_at DESC 
            LIMIT 1
        ) as last_message_time
    FROM messages m
    JOIN users u ON u.user_id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id
        ELSE m.sender_id
    END
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY last_message_time DESC
");
$stmt->execute([
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
]);
$conversations = $stmt->fetchAll();

// Get messages for specific conversation
$selected_user = null;
$messages = [];
if (isset($_GET['user'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_GET['user']]);
    $selected_user = $stmt->fetch();

    if ($selected_user) {
        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.profile_picture
            FROM messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([
            $_SESSION['user_id'], $_GET['user'],
            $_GET['user'], $_SESSION['user_id']
        ]);
        $messages = $stmt->fetchAll();

        // Mark messages as read
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET is_read = TRUE 
            WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$_GET['user'], $_SESSION['user_id']]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../img/lego_1-ummah.svg" />
    <link rel="stylesheet" href="./assets/mainPageStyle.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Messages</title>
</head>
<body>
    <div class="content-wrapper">
        <!-- Icon Sidebar -->
        <div class="icon-sidebar">
            <a href="homePage.php" class="icon home12"><i class="fas fa-home"></i></a>
            <a href="friends.php" class="icon friends12"><i class="fas fa-user-friends"></i></a>
            <a href="messages.php" class="icon comments12"><i class="fas fa-comment-alt"></i></a>
            <a href="notification.php" class="icon bell12"><i class="fas fa-bell"></i></a>
            <a href="settings.php" class="icon settings12"><i class="fas fa-cog"></i></a>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="messages-container">
                <!-- Conversations List -->
                <div class="conversations-list">
                    <?php foreach ($conversations as $conv): ?>
                        <a href="?user=<?php echo $conv['other_user_id']; ?>" 
                           class="conversation-item <?php echo ($selected_user && $selected_user['user_id'] == $conv['other_user_id']) ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($conv['profile_picture'] ?? 'assets/default-avatar.png'); ?>" 
                                 alt="Profile" class="post-avatar">
                            <div class="conversation-info">
                                <h3><?php echo htmlspecialchars($conv['username']); ?></h3>
                                <p class="last-message"><?php echo htmlspecialchars($conv['last_message']); ?></p>
                                <span class="message-time">
                                    <?php echo date('M j, g:i a', strtotime($conv['last_message_time'])); ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Chat Area -->
                <?php if ($selected_user): ?>
                    <div class="chat-area">
                        <div class="chat-header">
                            <img src="<?php echo htmlspecialchars($selected_user['profile_picture'] ?? 'assets/default-avatar.png'); ?>" 
                                 alt="Profile" class="post-avatar">
                            <h2><?php echo htmlspecialchars($selected_user['username']); ?></h2>
                        </div>

                        <div class="messages-area" id="messages-area">
                            <?php foreach ($messages as $message): ?>
                                <div class="message <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                    </div>
                                    <span class="message-time">
                                        <?php echo date('g:i a', strtotime($message['created_at'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form class="message-form" action="send_message.php" method="POST">
                            <input type="hidden" name="receiver_id" value="<?php echo $selected_user['user_id']; ?>">
                            <textarea name="content" placeholder="Type a message..." required></textarea>
                            <button type="submit"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-chat-selected">
                        <i class="fas fa-comments"></i>
                        <p>Select a conversation to start messaging</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of messages
        const messagesArea = document.getElementById('messages-area');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Auto-refresh messages every 5 seconds
        if (<?php echo $selected_user ? 'true' : 'false'; ?>) {
            setInterval(() => {
                fetch(`get_messages.php?user=<?php echo $selected_user['user_id']; ?>`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.messages) {
                            const messagesArea = document.getElementById('messages-area');
                            messagesArea.innerHTML = data.messages;
                            messagesArea.scrollTop = messagesArea.scrollHeight;
                        }
                    });
            }, 5000);
        }
    </script>
    <script src="./javascript/mainPageStyle.js"></script>
</body>
</html>