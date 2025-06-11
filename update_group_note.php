<?php
include('inc/db.php'); // Include your database connection

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the required parameters are provided
    if (!empty($_POST['group_id']) && isset($_POST['notes'])) {
        $group_id = $_POST['group_id'];
        $notes = $_POST['notes'];
        $max_seats = isset($_POST['max_seats']) ? $_POST['max_seats'] : null; // Capture max_seats if provided

        // Initialize SQL and parameters array
        $sql = "UPDATE groups SET notes = :notes";
        $params = [':notes' => $notes, ':group_id' => $group_id];

        // Check if max_seats is provided and is numeric
        if ($max_seats !== null && is_numeric($max_seats)) {
            $sql .= ", max_seats = :max_seats"; // Append to SQL
            $params[':max_seats'] = intval($max_seats); // Store as integer
        }

        $sql .= " WHERE group_id = :group_id";

        // Prepare and execute the update statement
        $stmt = $pdo->prepare($sql);
        
        // Attempt to execute the update
        if ($stmt->execute($params)) {
            // Redirect to a success page (or display a success message)
            header("Location: program.php?group_id=" . urlencode($group_id));
            exit();
        } else {
            echo "Error updating notes.";
        }
    } else {
        echo "Group ID or Notes is not set.";
    }
} else {
    echo "Invalid request method.";
}
?>