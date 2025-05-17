<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../SignIn-SignUp-Form/signin.php');
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $bio = trim($_POST['bio']);
            $profile_picture = $user['profile_picture'];

            // Handle profile picture upload
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        $profile_picture = $upload_path;
                    }
                }
            }

            try {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, bio = ?, profile_picture = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $email, $bio, $profile_picture, $_SESSION['user_id']]);
                header('Location: settings.php?success=1');
                exit();
            } catch (PDOException $e) {
                $error = "Error updating profile";
            }
        } elseif ($_POST['action'] === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if ($new_password === $confirm_password) {
                if (password_verify($current_password, $user['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    header('Location: settings.php?success=2');
                    exit();
                } else {
                    $error = "Current password is incorrect";
                }
            } else {
                $error = "New passwords do not match";
            }
        } elseif ($_POST['action'] === 'logout') {
            session_destroy();
            header('Location: ../SignIn-SignUp-Form/signin.php');
            exit();
        }
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
    <title>Settings</title>
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
            <div class="settings-container">
                <?php if (isset($_GET['success'])): ?>
                    <div class="success-message">
                        <?php
                        switch ($_GET['success']) {
                            case 1:
                                echo "Profile updated successfully!";
                                break;
                            case 2:
                                echo "Password changed successfully!";
                                break;
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Profile Settings -->
                <div class="settings-section">
                    <h2>Profile Settings</h2>
                    <form method="POST" enctype="multipart/form-data" class="settings-form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <?php if ($user['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Current profile picture" class="current-profile-picture">
                            <?php endif; ?>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                        </div>

                        <button type="submit" class="save-button">Save Changes</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="settings-section">
                    <h2>Change Password</h2>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="save-button">Change Password</button>
                    </form>
                </div>

                <!-- Logout -->
                <div class="settings-section">
                    <h2>Logout</h2>
                    <form method="POST" class="settings-form">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="logout-button">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


<script src="./javaScript/mainPageStyle.js"></script>