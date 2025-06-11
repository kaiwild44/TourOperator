<?php 
include('inc/header.php'); 

// Initialize totalTime variable
$totalTime = ""; // Default value for total time
$totalTime = ""; // Default value for total time
$totalSales = 0; // Initialize total sales variable


// Check if the 'id' parameter is present in the URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Fetch user data based on the user ID from the URL parameter
    $query = "SELECT * FROM users WHERE Id = :id"; 
    $stmt = $pdo->prepare($query);
    $stmt->execute(array(':id' => $userId)); 

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Separate date and time
        $dateRegistered = date('d/m/Y', strtotime($user['Date_Registered'])); 
        $timeRegistered = date('h:i A', strtotime($user['Date_Registered'])); 
    
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
    
        // Fetch the total number of sales for the user from the booking table
        $querySales = "SELECT COUNT(*) AS total_sales FROM booking WHERE agent_id = :agent_id";
        $stmtSales = $pdo->prepare($querySales);
        $stmtSales->execute(array(':agent_id' => $user['Id'])); 
        $totalSales = $stmtSales->fetch(PDO::FETCH_ASSOC)['total_sales'];
    
        // Fetch monthly sales data including total cost and number of sales
        $queryMonthlySales = "
            SELECT 
                YEAR(booking_date) AS year, 
                MONTH(booking_date) AS month, 
                SUM(price) AS total_sales, 
                COUNT(*) AS number_of_sales, 
                SUM(adult_food + child_food + adult_tickets + child_tickets) AS total_cost
            FROM booking 
            WHERE agent_id = :agent_id 
            GROUP BY YEAR(booking_date), MONTH(booking_date) 
            ORDER BY year DESC, month DESC";
        
        $stmtMonthlySales = $pdo->prepare($queryMonthlySales);
        $stmtMonthlySales->execute(array(':agent_id' => $userId)); 
        $monthlySalesData = $stmtMonthlySales->fetchAll(PDO::FETCH_ASSOC);
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

    // Fetch monthly sales data including total cost
    $queryMonthlySales = "
        SELECT 
            YEAR(booking_date) AS year, 
            MONTH(booking_date) AS month, 
            SUM(price) AS total_sales, 
            SUM(adult_food + child_food + adult_tickets + child_tickets) AS total_cost,
            COUNT(*) AS number_of_sales 
        FROM booking 
        WHERE agent_id = :agent_id 
        GROUP BY YEAR(booking_date), MONTH(booking_date) 
        ORDER BY year DESC, month DESC";

    $stmtMonthlySales = $pdo->prepare($queryMonthlySales);
    $stmtMonthlySales->execute(array(':agent_id' => $user['Id'])); 
    $monthlySalesData = $stmtMonthlySales->fetchAll(PDO::FETCH_ASSOC);
}

// Display user profile information
?>

<div class="container">
    <div class="wrapper-fit-centered user">
        <h2><?php echo htmlspecialchars($user['First_Name'] . ' ' . $user['Last_Name']); ?></h2>
        <img src="<?php echo htmlspecialchars($user['Image']); ?>" alt="User Image" style="width: 200px; height: auto;"><br>
        <p>Username: <strong><?php echo htmlspecialchars($user['Username']); ?></strong></p>
        <p>Role: <strong><?php echo htmlspecialchars($user['Role']); ?></strong></p>
        <p>Date Registered: <strong><?php echo $dateRegistered; ?></strong></p>
        <p>Tenure: <strong><?php echo $totalTime; ?></strong></p>
        <p>Total Sales: <strong><?php echo htmlspecialchars($totalSales); ?></strong></p>
        <hr>
        
        <p><strong>Monthly Sales:</strong></p>
        <?php
        // Display monthly sales data
        if ($monthlySalesData) {
            foreach ($monthlySalesData as $monthlySale) {
                $monthName = date("m/Y", mktime(0, 0, 0, $monthlySale['month'], 1));
                $totalSalesMonthly = $monthlySale['total_sales'];
                $totalCostMonthly = $monthlySale['total_cost'];
                $numberOfSalesMonthly = $monthlySale['number_of_sales'];
                $netPriceMonthly = $totalSalesMonthly - $totalCostMonthly; // Calculate net price
                
                // Check user role and display accordingly
                if ($_SESSION['role'] === 'Superadmin' || $_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'Sales_Manager') {
                    // Display both total sales and net price
                    echo "<p>{$monthName}: <strong>" . htmlspecialchars(number_format($totalSalesMonthly, 2)) . " AZN</strong> / <strong>" . htmlspecialchars(number_format($netPriceMonthly, 2)) . " AZN</strong> / <strong>" . htmlspecialchars($numberOfSalesMonthly) . " sales</strong></p>";
                } else {
                    // Only display net price for other roles
                    echo "<p>{$monthName}: <strong>" . htmlspecialchars(number_format($netPriceMonthly, 2)) . " AZN</strong></p>";
                }
            }
        } else {
            echo "<p>No monthly sales data available.</p>";
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