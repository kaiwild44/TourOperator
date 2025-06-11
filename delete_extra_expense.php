<?php
include('inc/db.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_id = $_POST['expense_id']; // Get the expense ID to delete
    $group_id = $_POST['group_id']; // Get the group ID to redirect later

    try {
        // Prepare the delete statement
        $stmtDelete = $pdo->prepare("DELETE FROM extra_expenses WHERE id = :id");
        $stmtDelete->execute([':id' => $expense_id]);

        // Redirect back to the program page after deleting
        header("Location: program.php?group_id=" . urlencode($group_id)); // Redirect to the same group
        exit();
        
    } catch (PDOException $e) {
        error_log("Error deleting extra expense: " . $e->getMessage());
        echo "There was an issue deleting the expense. Please try again.";
        exit();
    }
} else {
    // Redirect if accessed directly without POST data
    header("Location: index.php");
    exit();
}