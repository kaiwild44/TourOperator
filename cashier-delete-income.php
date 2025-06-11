<?php
// Include your PDO connection file
include('inc/db.php'); // Adjust this according to your file structure

session_start();

// Check if the user is not Superadmin or Admin or Sales_Manager
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Check if the ID parameter is set and is a valid integer
if(isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
    // Sanitize the ID parameter to prevent SQL injection
    $incomeId = $_POST['id'];

    try {
        // Prepare the SQL statement to delete the income entry
        $sql = "DELETE FROM income WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        // Execute the SQL statement
        $stmt->execute([$incomeId]);

        // If deletion is successful, redirect back to the cashier.php page
        header("Location: cashier.php");
        exit();
    } catch (PDOException $e) {
        // If deletion fails, display an error message
        echo "Error: " . $e->getMessage();
    }
} else {
    // If the ID parameter is missing or invalid, display an error message
    echo "Error: Invalid request.";
}
?>
