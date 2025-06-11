<?php
include('inc/db.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['group_id'])) {
        $group_id = $_POST['group_id'];

        try {
            // Update the board_display to 1 for the specified group_id
            $sql = "UPDATE groups SET board_display = 1 WHERE group_id = :group_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->execute();

            // Redirect to index.php after the update
            header("Location: index.php"); // Change the redirection as per your requirement
            exit();

        } catch (PDOException $e) {
            // Handle any errors here
            echo "Error updating group display: " . $e->getMessage();
        }
    }
} else {
    // Handle invalid request method
    header("Location: index.php"); // Redirect if the request method is not POST
    exit();
}
