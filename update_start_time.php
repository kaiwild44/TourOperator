<?php
session_start();
include('inc/db.php');
include('inc/functions.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = filter_input(INPUT_POST, 'group_id', FILTER_SANITIZE_STRING);
    $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($group_id)) {
        $_SESSION['message'] = "Error: Group ID is required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    if (empty($start_time)) {
        $_SESSION['message'] = "Error: Start time is required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
    //Validate start time format. Add more thorough checking if needed.
    if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time)) {
        $_SESSION['message'] = "Error: Invalid start time format. Use HH:MM (24-hour format).";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    try {
        $sql = "UPDATE groups SET start_time = :start_time WHERE group_id = :group_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':group_id', $group_id);
        $stmt->execute();

        //Store the updated start time in the session
        $_SESSION['updated_start_time'] = array($group_id => $start_time); //Store as key-value pair

        $_SESSION['message'] = "Start time successfully updated for group ID: $group_id.";
    } catch (PDOException $e) {
        error_log("Database error in update_start_time.php: " . $e->getMessage());
        $_SESSION['message'] = "Database error updating start time. Please try again.";
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>