<?php
include('inc/db.php');
include('inc/functions.php');

// Check if the user is authorized
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve pickup time and booking IDs
    $pickupTimes = $_POST['pickup_time']; // This is an array
    $bookingIds = $_POST['booking_ids']; // This is also an array
    $groupId = $_POST['group_id']; // Get group_id from the POST request

    // Validate input
    if (empty($pickupTimes) || empty($bookingIds) || count($pickupTimes) !== count($bookingIds)) {
        echo "Error: Pickup times or Booking IDs are missing or do not match.";
        exit();
    }

    try {
        // Loop through each booking ID and corresponding pickup time
        for ($i = 0; $i < count($pickupTimes); $i++) {
            $pickupTime = $pickupTimes[$i];
            $bookingId = $bookingIds[$i];

            // Prepare the SQL update statement for each booking
            $updateQuery = "UPDATE booking SET pickup_time = :pickup_time WHERE id = :booking_id";
            $stmt = $pdo->prepare($updateQuery);
            
            // Bind parameters
            $stmt->bindParam(':pickup_time', $pickupTime);
            $stmt->bindParam(':booking_id', $bookingId);

            // Execute the statement
            $stmt->execute();
        }

        $_SESSION['message'] = "Pickup times updated successfully.";
    } catch (PDOException $e) {
        echo "Error updating pickup time: " . $e->getMessage();
        exit();
    }

    // Redirect back to the program page with the group_id
    header("Location: program.php?group_id=" . urlencode($groupId)); // Use the retrieved group_id
    exit();
}
?>