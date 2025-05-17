<?php
include "./config.php";

$username = $_POST["Username"];
$email = $_POST["Email"];
$password = password_hash($_POST["Password"], PASSWORD_DEFAULT); // Hash the password

try {
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $req = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($req, "sss", $username, $email, $password);
    mysqli_stmt_execute($req);
    mysqli_stmt_close($req);
    header("Location: ../index.html");
} catch (Exception $e) {
    echo "Data not saved properly";
    echo $e->getMessage();
}
?>