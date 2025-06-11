<?php
include('inc/db.php'); // Include your database connection

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the required parameters are provided
    if (!empty($_POST['group_id'])) {
        $group_id = $_POST['group_id'];
        $max_seats = isset($_POST['max_seats']) ? $_POST['max_seats'] : null; // Capture max_seats if provided
        $time_display = isset($_POST['time_display']) ? 1 : 0; // Capture board_display status

        // Initialize SQL and parameters array
        $sql = "UPDATE groups SET";
        $params = [];

        // Check if max_seats is provided and is numeric
        if ($max_seats !== null && is_numeric($max_seats)) {
            $sql .= " max_seats = :max_seats,"; // Append to SQL
            $params[':max_seats'] = intval($max_seats); // Store as integer
        }

        // Update the board_display status
        $sql .= " time_display = :time_display"; // Append to SQL
        $params[':time_display'] = $time_display; // Store time_display status

        // Finalize the SQL statement with the WHERE clause
        $sql .= " WHERE group_id = :group_id";
        $params[':group_id'] = $group_id; // Add group_id to parameters

        // Prepare and execute the update statement
        $stmt = $pdo->prepare($sql);

        // Attempt to execute the update
        if ($stmt->execute($params)) {
            // Redirect to a success page (or display a success message)
            header("Location: program.php?group_id=" . urlencode($group_id));
            exit();
        } else {
            echo "Error updating max seats or board display.";
        }
    } else {
        echo "Group ID is not set.";
    }
} else {
    echo "Invalid request method.";
}
?>