<?php
include('inc/db.php');
include('inc/functions.php');

// Check if the user is authorized
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

try {
    // Prepare the SQL statement to delete empty groups
    $deleteQuery = "
        DELETE FROM groups
        WHERE group_id NOT IN (
            SELECT DISTINCT group_id FROM booking
        )
    ";

    $stmt = $pdo->prepare($deleteQuery);
    $stmt->execute();

    $_SESSION['message'] = "Empty groups removed successfully.";

} catch (PDOException $e) {
    $_SESSION['message'] = "Error removing empty groups: " . $e->getMessage();
}

// Redirect back to groups.php
header("Location: groups.php");
exit();
?>