<?php
include('inc/header.php'); // This includes your db.php and initializes $pdo

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Fetch drivers with role 'Driver'
try {
    $driversQuery = "SELECT Id, First_Name, Last_Name FROM users WHERE Role = 'Driver'";
    $driversResult = $pdo->query($driversQuery);
    $drivers = $driversResult->fetchAll();

    // Specify the date range you want to display (current month)
    $start_date = new DateTime('first day of this month');
    $end_date = new DateTime('last day of this month');
    $end_date->modify('+1 day');

    // Fetch group assignments for the specified date range
    $groupsQuery = "
        SELECT driver_id, group_date, tour_name 
        FROM groups 
        WHERE group_date BETWEEN ? AND ?
    ";
    $stmt = $pdo->prepare($groupsQuery);
    
    // Format the dates
    $startDateFormatted = $start_date->format('Y-m-d');
    $endDateFormatted = $end_date->format('Y-m-d');

    $stmt->bindParam(1, $startDateFormatted);
    $stmt->bindParam(2, $endDateFormatted);
    $stmt->execute();
    $groupsResult = $stmt->fetchAll();

    // Create an array to store group data for quick lookup
    $groupsData = [];
    foreach ($groupsResult as $row) {
        $date = $row['group_date'];
        $driverId = $row['driver_id'];
        $groupsData[$date][$driverId] = $row['tour_name']; // Just the tour name
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Handle form submission for day-off or office day
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driverId = $_POST['driver_id'];
    $availabilityDate = $_POST['availability_date'];
    $type = $_POST['type']; // 'day_off' or 'office_day'

    // Check for conflicts
    $conflictQuery = "
        SELECT COUNT(*) FROM groups 
        WHERE driver_id = ? AND group_date = ?
    ";
    $conflictStmt = $pdo->prepare($conflictQuery);
    $conflictStmt->execute([$driverId, $availabilityDate]);
    $conflictCount = $conflictStmt->fetchColumn();

    if ($conflictCount > 0) {
        $errorMessage = "Conflict: The driver is already assigned to a tour on that date.";
    } else {
        // No conflict; proceed to insert
        $insertQuery = "
            INSERT INTO driver_availability (driver_id, availability_date, type) 
            VALUES (?, ?, ?)
        ";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$driverId, $availabilityDate, $type]);

        // Display success message
        $successMessage = "Successfully added $type for the selected driver.";
    }
}

// Fetch driver availability for the specified date range
$availabilityQuery = "
    SELECT driver_id, availability_date, type 
    FROM driver_availability 
    WHERE availability_date BETWEEN ? AND ?
";
$availabilityStmt = $pdo->prepare($availabilityQuery);
$availabilityStmt->execute([$startDateFormatted, $endDateFormatted]);
$availabilityResult = $availabilityStmt->fetchAll(PDO::FETCH_ASSOC);

$availabilityData = [];
foreach ($availabilityResult as $row) {
    $availabilityData[$row['availability_date']][$row['driver_id']] = $row['type'];
}

// Combining data for display
$combinedData = [];
$interval = DateInterval::createFromDateString('1 day');
$dateRange = new DatePeriod($start_date, $interval, $end_date);
foreach ($dateRange as $date) {
    $dateStr = $date->format('Y-m-d');
    foreach ($drivers as $driver) {
        $driverId = $driver['Id'];
        $combinedData[$dateStr][$driverId] = [
            'tour' => isset($groupsData[$dateStr][$driverId]) ? $groupsData[$dateStr][$driverId] : '',
            'availability' => isset($availabilityData[$dateStr][$driverId]) ? $availabilityData[$dateStr][$driverId] : ''
        ];
    }
}
?>

<style>
    .flex-form {
        display: flex;
        align-items: center; /* Align items vertically centered */
        justify-content: flex-start; /* Align items to the left */
        flex-wrap: wrap; /* Wrap elements if needed */
    }

    .form-group {
        margin-right: 20px; /* Add spacing between form groups */
        display: flex;
        flex-direction: column; /* Stack label above input/select */
    }

    .btn {
        align-self: center; /* Center the button vertically in the flex item */
        padding: 8px 16px; /* Add padding for a better button size */
    }

    .light-orange {
        background-color: #ffcc80; /* Light orange */
    }

    .light-blue {
        background-color: #80d3ff; /* Light blue */
    }
</style>

<h1>Drivers' Schedule</h1><br>

<!-- Form to Add Day Off or Office Day -->
<div class="form-container wrapper-fit-centered">

    <?php if (isset($errorMessage)): ?>
        <div class="msg msg-error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>
    <?php if (isset($successMessage)): ?>
        <div class="msg msg-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <form action="" method="POST" class="flex-form">
        <div class="form-group">
            <label for="driver_id">Select Driver:</label>
            <select name="driver_id" id="driver_id" required>
                <option value="" required>Select One</option>
                <?php foreach ($drivers as $driver): ?>
                    <option value="<?php echo $driver['Id']; ?>">
                        <?php echo htmlspecialchars($driver['First_Name'] . ' ' . $driver['Last_Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="availability_date">Date:</label>
            <input type="date" name="availability_date" required>
        </div>

        <div class="form-group">
            <label for="type">Type:</label>
            <select name="type" id="type">
                <option value="">Select One</option>
                <option value="day_off">Day Off</option>
                <option value="office_day">Office Day</option>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" class="btn">Apply</button>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="table font-small-8">
        <thead>
            <tr class="sticky-header">
                <th>Day</th>
                <?php foreach ($drivers as $driver): ?>
                    <th><?php echo htmlspecialchars($driver['First_Name'] . ' ' . $driver['Last_Name']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody id="table-data">
            <?php
            // Display data for each day of the month
            $count = 0; // Counter for alternating row classes

            foreach ($dateRange as $date):
                $dateStr = $date->format('Y-m-d');
                $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row'; // Determine the row class
                $count++; // Increment counter
            ?>
                <tr class="<?php echo $rowClass; ?>">
                    <td><?php echo $date->format('j'); // Display the day of the month ?></td>
                    <?php foreach ($drivers as $driver): 
                        $driverId = $driver['Id'];
                        $tourName = htmlspecialchars($combinedData[$dateStr][$driverId]['tour']);
                        $availabilityStatus = htmlspecialchars($combinedData[$dateStr][$driverId]['availability']); // e.g., "day_off" or "office_day"
                        // Determine the class for availability
                        $availabilityClass = '';
                        if ($availabilityStatus === 'day_off') {
                            $availabilityClass = 'light-orange'; // Light orange class for Day Off
                        } elseif ($availabilityStatus === 'office_day') {
                            $availabilityClass = 'light-blue'; // Light blue class for Office Day
                        }
                    ?>
                        <td class="<?php echo $availabilityClass; ?>">
                            <?php 
                            // Display availability status
                            if ($availabilityStatus) {
                                // Convert status to user-friendly format
                                $userFriendlyStatus = ucfirst(str_replace('_', ' ', $availabilityStatus)); // Convert 'day_off' to 'Day Off'
                                echo "<span class='availability'>$userFriendlyStatus</span><br>";
                            }
                            // Display tour name if available
                            echo $tourName;                             ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php include('inc/footer.php'); ?>