<?php 
include('inc/header.php'); 

// Set the time zone to Baku, Azerbaijan
date_default_timezone_set('Asia/Baku');

// Get the current date and time
$currentDateTime = date('Y-m-d H:i:s');

// Check if the 'id' parameter is present in the URL
if(isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Fetch user data based on the user ID from the URL parameter
    $query = "SELECT * FROM users WHERE Id = :id"; 
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $userId)); 

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user) {
        // Separate date and time
        $dateRegistered = date('d F Y', strtotime($user['Date_Registered'])); 
        $timeRegistered = date('h:i A', strtotime($user['Date_Registered'])); 

        // Fetch the sum of sales for the user from the booking table
        $querySales = "SELECT SUM(price) AS total_sales FROM booking WHERE agent_id = :agent_id";
        $stmtSales = $pdo->prepare($querySales);
        $stmtSales->execute(array(':agent_id' => $userId)); 
        $totalSales = $stmtSales->fetch(PDO::FETCH_ASSOC)['total_sales'];

        // Fetch the sum of prices for the sales made by the user
        $queryCompanyEarned = "SELECT SUM(price) AS total_earned FROM booking WHERE agent_id = :agent_id";
        $stmtCompanyEarned = $pdo->prepare($queryCompanyEarned);
        $stmtCompanyEarned->execute(array(':agent_id' => $userId)); 
        $totalEarned = $stmtCompanyEarned->fetch(PDO::FETCH_ASSOC)['total_earned'];
    } else {
        // User not found, handle the error or redirect
        header("Location: error.php");
        exit();
    }
} else {
    // If 'id' parameter is not present, fetch the profile of the currently logged-in user
    $query = "SELECT * FROM users WHERE Username = :username"; 
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':username' => $_SESSION['username'])); 

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Separate date and time
    $dateRegistered = date('d F Y', strtotime($user['Date_Registered'])); 
    $timeRegistered = date('h:i A', strtotime($user['Date_Registered'])); 

    // Fetch the sum of sales for the user from the booking table
    $querySales = "SELECT SUM(price) AS total_sales FROM booking WHERE agent_id = :agent_id";
    $stmtSales = $pdo->prepare($querySales);
    $stmtSales->execute(array(':agent_id' => $user['Id'])); 
    $totalSales = $stmtSales->fetch(PDO::FETCH_ASSOC)['total_sales'];

    // Fetch the sum of prices for the sales made by the user
    $queryCompanyEarned = "SELECT SUM(price) AS total_earned FROM booking WHERE agent_id = :agent_id";
    $stmtCompanyEarned = $pdo->prepare($queryCompanyEarned);
    $stmtCompanyEarned->execute(array(':agent_id' => $user['Id'])); 
    $totalEarned = $stmtCompanyEarned->fetch(PDO::FETCH_ASSOC)['total_earned'];
}

// Calculate total time in the company
$dateRegisteredTimestamp = strtotime($user['Date_Registered']);
$currentTimestamp = time();
$timeDifference = $currentTimestamp - $dateRegisteredTimestamp;
$totalTimeYears = floor($timeDifference / (365 * 24 * 60 * 60));
$totalTimeMonths = floor(($timeDifference - $totalTimeYears * 365 * 24 * 60 * 60) / (30 * 24 * 60 * 60));
$totalTimeDays = floor(($timeDifference - $totalTimeYears * 365 * 24 * 60 * 60 - $totalTimeMonths * 30 * 24 * 60 * 60) / (24 * 60 * 60));

// Format the total time appropriately
$totalTime = "";
if ($totalTimeYears > 0) {
    $totalTime .= $totalTimeYears . " year" . ($totalTimeYears > 1 ? "s" : "");
}
if ($totalTimeMonths > 0) {
    $totalTime .= ($totalTime != "" ? ", " : "") . $totalTimeMonths . " month" . ($totalTimeMonths > 1 ? "s" : "");
}
if ($totalTimeDays > 0) {
    $totalTime .= ($totalTime != "" ? ", " : "") . $totalTimeDays . " day" . ($totalTimeDays > 1 ? "s" : "");
}
$totalTime = ($totalTime != "" ? $totalTime : "Less than a day");
?>

<div class="container">
    <div class="wrapper-fit-centered user">
        <h1>My Programs</h1><br>
        <h2><?php echo $user['First_Name'] . ' ' . $user['Last_Name']; ?></h2><br>

        <?php
        // Check if the user is associated with any groups
        $groupsQuery = "
            SELECT g.group_id, g.group_date, g.guide_lang, g.tour_name
            FROM groups g 
            WHERE 
                (g.guide_id = :guide_id OR 
                 g.second_guide_id = :second_guide_id OR 
                 g.driver_id = :driver_id)
                AND g.group_date >= CURDATE()  -- Filter for today and future groups
            ORDER BY g.group_date ASC  -- Sorting results by date ascending
        ";
            
            $stmtGroups = $pdo->prepare($groupsQuery);
            $stmtGroups->execute(array(
                ':guide_id' => $user['Id'], 
                ':second_guide_id' => $user['Id'],
                ':driver_id' => $user['Id']  // Add this line to check for driver ID as well
            ));
            $groups = $stmtGroups->fetchAll(PDO::FETCH_ASSOC);

            if ($groups) {
                echo "<table class='table'>"; // Start the table
                echo "<thead><tr>
                        <th>Tour Date</th>
                        <th>Group ID</th>
                        <th>Lang    </th>
                    </tr></thead>";
                echo "<tbody>"; // Start the body of the table
                foreach ($groups as $group) {
                    echo "<tr>";
                    echo "<td>" . date('d-m-Y', strtotime($group['group_date'])) . "</td>";
                    echo "<td><a href='program.php?group_id=" . htmlspecialchars($group['group_id']) . "'>" . 
                        htmlspecialchars($group['tour_name']) . "</a></td>"; // Display the Tour Name
                    echo "<td>" . htmlspecialchars($group['guide_lang']) . "</td>"; // Guide Language
                    echo "</tr>";
                }
                echo "</tbody></table>"; // Close the tbody and table
            } else {
                echo "<p>You are not currently assigned to any groups.</p>"; // Message for no assigned groups
            }
        ?>
    </div>
</div>


<?php include('inc/footer.php'); ?>

<?php
function getTotalBookings($pdo, $agentId) {
    $query = "SELECT COUNT(*) AS total_bookings FROM booking WHERE agent_id = :agent_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':agent_id' => $agentId)); 
    $totalBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];
    return $totalBookings;
}

function getTotalSalesAmount($pdo, $agentId) {
    $query = "SELECT SUM(price) AS total_sales FROM booking WHERE agent_id = :agent_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':agent_id' => $agentId)); 
    $totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];
    return $totalSales;
}
?>