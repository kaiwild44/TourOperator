<?php
include('inc/db.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group_id = $_POST['group_id'];
    
    try {
        // Fetch current bookings for the group
        $stmt = $pdo->prepare("SELECT * FROM booking WHERE group_id = :group_id");
        $stmt->execute(['group_id' => $group_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Aggregate bookings by language
        $russianBookings = [];
        $englishBookings = []; 
        foreach ($bookings as $booking) {
            if ($booking['lang'] === 'Rus') {  // Fixed 'language' to 'lang'
                $russianBookings[] = $booking;
            } else if ($booking['lang'] === 'Eng') {
                $englishBookings[] = $booking;
            }
        }

        // Function to generate a new group ID or reuse existing ones
        function generateNewGroupId($baseGroupId, $suffix, $pdo) {
            $newGroupId = $baseGroupId . $suffix;
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM groups WHERE group_id = :group_id");
            $stmtCheck->execute(['group_id' => $newGroupId]);
            if ($stmtCheck->fetchColumn() == 0) {
                $stmtInsertGroup = $pdo->prepare("INSERT INTO groups (group_id, group_date, max_seats) VALUES (:group_id, CURDATE(), 18)");
                $stmtInsertGroup->execute(['group_id' => $newGroupId]);
            }
            return $newGroupId; // Ensure to return the new or existing group_id
        }

        // Base group ID assumed to exclude the last character denoting uniqueness
        $baseGroupId = substr($group_id, 0, -1);

        // Create or find new group IDs
        $russianGroupId = generateNewGroupId($baseGroupId, 'R', $pdo);
        $englishGroupId = generateNewGroupId($baseGroupId, 'E', $pdo);

        // Update the bookings with the new group IDs
        foreach ($russianBookings as $booking) {
            $stmtUpdate = $pdo->prepare("UPDATE booking SET group_id = :new_group_id WHERE id = :id");
            $stmtUpdate->execute(['new_group_id' => $russianGroupId, 'id' => $booking['id']]);
        }
        foreach ($englishBookings as $booking) {
            $stmtUpdate = $pdo->prepare("UPDATE booking SET group_id = :new_group_id WHERE id = :id");
            $stmtUpdate->execute(['new_group_id' => $englishGroupId, 'id' => $booking['id']]);
        }

        // Redirect back to the board page to reflect changes
        header('Location: board.php');
        exit;
        
    } catch (PDOException $e) {
        echo "Error redistributing tourists: " . $e->getMessage();
    }
}
?>
