<?php
session_start(); 
include "./config.php";
$username = $_POST["Username"];
$password = $_POST["Password"];

try {
    $sql = "SELECT * FROM users WHERE username = ?";
    $req = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($req, "s", $username);
    mysqli_stmt_execute($req);
    $result = mysqli_stmt_get_result($req);
    
    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            header("Location: ../mainPage/homePage.php");
            mysqli_stmt_close($req);
            $conn->close();
            exit();
        }
    }
    header("Location: ../index.html");
} catch (Exception $e) {
    echo 'Connection error <br>';
    echo $e->getMessage();
}
?>