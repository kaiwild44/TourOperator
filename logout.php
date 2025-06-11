<?php
session_start();

require 'inc/db.php';

if (isset($_SESSION['user_id'])) {
    // Update the logout time in user_logs table
    $user_id = $_SESSION['user_id'];
    $logoutQuery = "UPDATE user_logs SET logout_time = NOW() WHERE user_id = ? AND logout_time IS NULL";
    $stmt = $pdo->prepare($logoutQuery);
    $stmt->execute([$user_id]);

    session_destroy();
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
}

header("Location: login.php");
exit();
?>
