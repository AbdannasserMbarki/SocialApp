<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../SignIn-SignUp-Form/signin.php');
    exit();
}

// Mark notifications as read
if (isset($_POST['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header('Location: notification.php');
    exit();
}

// Get notifications
$stmt = $pdo->prepare("
    SELECT n.*, 
           CASE 
               WHEN n.type = 'like' THEN (SELECT username FROM users WHERE user_id = (SELECT user_id FROM likes WHERE like_id = n.reference_id))
               WHEN n.type = 'friend_request' THEN (SELECT username FROM users WHERE user_id = (SELECT user_id1 FROM friends WHERE friendship_id = n.reference_id))
               WHEN n.type = 'message' THEN (SELECT username FROM users WHERE user_id = (SELECT sender_id FROM messages WHERE message_id = n.reference_id))
               WHEN n.type = 'friend_accept' THEN (SELECT username FROM users WHERE user_id = (SELECT user_id2 FROM friends WHERE friendship_id = n.reference_id))
           END as actor_username,
           CASE 
               WHEN n.type = 'like' THEN (SELECT content FROM posts WHERE post_id = (SELECT post_id FROM likes WHERE like_id = n.reference_id))
               WHEN n.type = 'message' THEN (SELECT content FROM messages WHERE message_id = n.reference_id)
               ELSE NULL
           END as reference_content
    FROM notifications n
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Get unread count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$_SESSION['user_id']]);
$unread_count = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../img/lego_1-ummah.svg" />
    <link rel="stylesheet" href="./assets/mainPageStyle.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Notifications</title>
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
            <div class="notifications-container">
                <div class="notifications-header">
                    <h2>Notifications <?php echo $unread_count ? "($unread_count)" : ''; ?></h2>
                    <?php if ($unread_count): ?>
                        <form method="POST" class="mark-read-form">
                            <input type="hidden" name="mark_read" value="1">
                            <button type="submit">Mark all as read</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="no-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                <div class="notification-icon">
                                    <?php
                                    switch ($notification['type']) {
                                        case 'like':
                                            echo '<i class="fas fa-heart"></i>';
                                            break;
                                        case 'friend_request':
                                            echo '<i class="fas fa-user-plus"></i>';
                                            break;
                                        case 'message':
                                            echo '<i class="fas fa-comment"></i>';
                                            break;
                                        case 'friend_accept':
                                            echo '<i class="fas fa-user-check"></i>';
                                            break;
                                    }
                                    ?>
                                </div>
                                <div class="notification-content">
                                    <?php
                                    switch ($notification['type']) {
                                        case 'like':
                                            echo sprintf(
                                                '<p><strong>%s</strong> liked your post: "%s"</p>',
                                                htmlspecialchars($notification['actor_username']),
                                                htmlspecialchars(substr($notification['reference_content'], 0, 50)) . '...'
                                            );
                                            break;
                                        case 'friend_request':
                                            echo sprintf(
                                                '<p><strong>%s</strong> sent you a friend request</p>',
                                                htmlspecialchars($notification['actor_username'])
                                            );
                                            break;
                                        case 'message':
                                            echo sprintf(
                                                '<p><strong>%s</strong> sent you a message: "%s"</p>',
                                                htmlspecialchars($notification['actor_username']),
                                                htmlspecialchars(substr($notification['reference_content'], 0, 50)) . '...'
                                            );
                                            break;
                                        case 'friend_accept':
                                            echo sprintf(
                                                '<p><strong>%s</strong> accepted your friend request</p>',
                                                htmlspecialchars($notification['actor_username'])
                                            );
                                            break;
                                    }
                                    ?>
                                    <span class="notification-time">
                                        <?php echo date('M j, g:i a', strtotime($notification['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<script src="./javascript/mainPageStyle.js"></script>