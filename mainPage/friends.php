<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../SignIn-SignUp-Form/signin.php');
    exit();
}

// Handle friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_request' && isset($_POST['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO friends (user_id1, user_id2) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $_POST['user_id']]);
    } elseif ($_POST['action'] === 'accept_request' && isset($_POST['request_id'])) {
        $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE friendship_id = ?");
        $stmt->execute([$_POST['request_id']]);
    }
    header('Location: friends.php');
    exit();
}

// Search users
$search_results = [];
if (isset($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $stmt = $pdo->prepare("
        SELECT u.*, 
               CASE 
                   WHEN f1.friendship_id IS NOT NULL THEN f1.status
                   WHEN f2.friendship_id IS NOT NULL THEN f2.status
                   ELSE NULL
               END as friendship_status,
               CASE 
                   WHEN f1.friendship_id IS NOT NULL THEN f1.friendship_id
                   WHEN f2.friendship_id IS NOT NULL THEN f2.friendship_id
                   ELSE NULL
               END as friendship_id
        FROM users u
        LEFT JOIN friends f1 ON f1.user_id2 = u.user_id AND f1.user_id1 = ?
        LEFT JOIN friends f2 ON f2.user_id1 = u.user_id AND f2.user_id2 = ?
        WHERE u.user_id != ? AND u.username LIKE ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $search]);
    $search_results = $stmt->fetchAll();
}

// Get friend requests
$stmt = $pdo->prepare("
    SELECT f.*, u.username, u.profile_picture
    FROM friends f
    JOIN users u ON f.user_id1 = u.user_id
    WHERE f.user_id2 = ? AND f.status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$friend_requests = $stmt->fetchAll();

// Get friends list
$stmt = $pdo->prepare("
    SELECT u.* FROM users u
    JOIN friends f ON (f.user_id1 = u.user_id OR f.user_id2 = u.user_id)
    WHERE (f.user_id1 = ? OR f.user_id2 = ?)
    AND f.status = 'accepted'
    AND u.user_id != ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$friends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../img/lego_1-ummah.svg" />
    <link rel="stylesheet" href="./assets/mainPageStyle.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Friends</title>
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
            <!-- Search Users -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <!-- Search Results -->
            <?php if (!empty($search_results)): ?>
                <div class="search-results">
                    <h2>Search Results</h2>
                    <?php foreach ($search_results as $user): ?>
                        <div class="user-card">
                            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile" class="post-avatar">
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                                <?php if ($user['friendship_status'] === null): ?>
                                    <form method="POST" class="friend-action">
                                        <input type="hidden" name="action" value="send_request">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit">Add Friend</button>
                                    </form>
                                <?php elseif ($user['friendship_status'] === 'pending'): ?>
                                    <span class="status pending">Friend Request Pending</span>
                                <?php else: ?>
                                    <span class="status friends">Friends</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Friend Requests -->
            <?php if (!empty($friend_requests)): ?>
                <div class="friend-requests">
                    <h2>Friend Requests</h2>
                    <?php foreach ($friend_requests as $request): ?>
                        <div class="request-card friend-card">
                            <img src="<?php echo htmlspecialchars($request['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile" class="post-avatar">
                            <div class="request-info">
                                <h3><?php echo htmlspecialchars($request['username']); ?></h3>
                                <form method="POST" class="request-action friend-action">
                                    <input type="hidden" name="action" value="accept_request">
                                    <input type="hidden" name="request_id" value="<?php echo $request['friendship_id']; ?>">
                                    <button type="submit">Accept Request</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Friends List -->
            <div class="friends-list">
                <h2>Your Friends</h2>
                <?php foreach ($friends as $friend): ?>
                    <div class="friend-card">
                        <img src="<?php echo htmlspecialchars($friend['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="Profile" class="post-avatar">
                        <h3><?php echo htmlspecialchars($friend['username']); ?></h3>
                        <div class="friend-info">
                            <a href="messages.php?user=<?php echo $friend['user_id']; ?>" class="message-button">
                                <i class="fas fa-comment"></i> Message
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
<script src="./javascript/mainPageStyle.js"></script>