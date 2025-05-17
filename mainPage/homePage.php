<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../SignIn-SignUp-Form/signin.php');
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get all posts with user information and like count
$stmt = $pdo->query("
    SELECT p.*, u.username, u.profile_picture,
           COUNT(DISTINCT l.like_id) as like_count,
           EXISTS(SELECT 1 FROM likes WHERE post_id = p.post_id AND user_id = {$_SESSION['user_id']}) as user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN likes l ON p.post_id = l.post_id
    GROUP BY p.post_id
    ORDER BY p.created_at DESC
");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../img/lego_1-ummah.svg" />
    <link rel="stylesheet" href="./assets/mainPageStyle.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Home</title>
</head>
<body>
    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Icon Sidebar -->
        <div class="icon-sidebar">
            <a href="homePage.php" class="icon home12"><i class="fas fa-home"></i></a>
            <a href="friends.php" class="icon friends12"><i class="fas fa-user-friends"></i></a>
            <a href="messages.php" class="icon comments12"><i class="fas fa-comment-alt"></i></a>
            <a href="notification.php" class="icon bell12"><i class="fas fa-bell"></i></a>
            <a href="settings.php" class="icon settings12"><i class="fas fa-cog"></i></a>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Create Post Section -->
            <div class="create-post">
                <form action="create_post.php" method="POST" enctype="multipart/form-data">
                    <textarea name="content" placeholder="What's on your mind?" required></textarea>
                    <input type="file" name="image" accept="image/*">
                    <button type="submit">Post</button>
                </form>
            </div>

            <!-- Posts Feed -->
            <div class="posts-feed">
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <div class="post-header">
                            <div class="post-header-left">
                                <img src="<?php echo htmlspecialchars($post['profile_picture'] ?? './assets/default-avatar.png'); ?>" alt="Profile" class="post-avatar">
                                <span class="post-username"><?php echo htmlspecialchars($post['username']); ?></span></div>
                            <div class="post-header-right">
                                <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                    <button class="delete-post-button" data-post-id="<?php echo $post['post_id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            <?php if ($post['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="Post image" class="post-image">
                            <?php endif; ?>
                        </div>
                        <div class="post-actions">
                            <button class="like-button <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                    data-post-id="<?php echo $post['post_id']; ?>">
                                <i class="fas fa-heart"></i>
                                <span class="like-count"><?php echo $post['like_count']; ?></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Friends Sidebar -->
        <div class="friends-sidebar">
            <div class="friends-header">Active Friends</div>
            <div class="friends-list">
                <?php
                $stmt = $pdo->prepare("
                    SELECT u.*, 
                           CASE 
                               WHEN u.last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 
                               ELSE 0 
                           END as is_online
                    FROM users u
                    JOIN friends f ON (f.user_id1 = u.user_id OR f.user_id2 = u.user_id)
                    WHERE (f.user_id1 = ? OR f.user_id2 = ?)
                    AND f.status = 'accepted'
                    AND u.user_id != ?
                    ORDER BY is_online DESC, u.last_activity DESC
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
                $friends = $stmt->fetchAll();

                if (empty($friends)): ?>
                    <div class="no-friends">No active friends</div>
                <?php else:
                    foreach ($friends as $friend): ?>
                        <div class="friend">
                            <div class="friend-avatar">
                                <img src="<?php echo htmlspecialchars($friend['profile_picture'] ?? 'assets/default-avatar.png'); ?>" alt="<?php echo htmlspecialchars($friend['username']); ?>">
                                <?php if ($friend['is_online']): ?>
                                    <span class="online-indicator"></span>
                                <?php endif; ?>
                            </div>
                            <div class="friend-info">
                                <div class="friend-name"><?php echo htmlspecialchars($friend['username']); ?></div>
                                <div class="friend-status"><?php echo $friend['is_online'] ? 'Online' : 'Offline'; ?></div>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>

    <script src="./javaScript/mainPageStyle.js"></script>
    <script src="./javaScript/online-users.js"></script>
    <script>
        // Like functionality
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', async function() {
                const postId = this.dataset.postId;
                try {
                    const response = await fetch('like_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ post_id: postId })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.classList.toggle('liked');
                        this.querySelector('.like-count').textContent = data.like_count;
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });

        // Delete post functionality
        document.querySelectorAll('.delete-post-button').forEach(button => {
            button.addEventListener('click', async function() {
                if (!confirm('Are you sure you want to delete this post?')) {
                    return;
                }
                
                const postId = this.dataset.postId;
                try {
                    const response = await fetch('delete_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ post_id: postId })
                    });
                    const data = await response.json();
                    if (data.success) {
                        // Remove the post from the DOM
                        this.closest('.post').remove();
                    } else {
                        alert(data.message || 'Error deleting post');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error deleting post');
                }
            });
        });
    </script>
</body>
</html>