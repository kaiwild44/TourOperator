<?php
include('inc/header.php');

// Set timezone to Baku, Azerbaijan
date_default_timezone_set('Asia/Baku');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Specify the month and year for the report
$currentMonth = date('n'); // Current month (1-12)
$currentYear = date('Y');
$start_date = new DateTime("first day of $currentYear-$currentMonth");
$end_date = new DateTime("last day of $currentYear-$currentMonth");
$end_date->modify('+1 day'); // Extend to the first day of the next month

// Check if custom date range is provided in the URL
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = new DateTime($_GET['start_date']);
    $end_date = new DateTime($_GET['end_date']);
    $end_date->modify('+1 day'); // Adjust to include the end date
}

// Fetch guides
try {
// Fetch drivers
$driversQuery = "SELECT Id, First_Name, Last_Name FROM users WHERE Role = 'Driver'";
$driversResult = $pdo->query($driversQuery);
$drivers = $driversResult->fetchAll(PDO::FETCH_ASSOC);

// Prepare arrays to hold the data
$driverAvailabilityData = [];
$driverToursData = [];

// Fetch availability data for drivers for the date range
foreach ($drivers as $driver) {
    $driverId = $driver['Id'];

    $availabilityQuery = "
        SELECT type, COUNT(*) AS count
        FROM driver_availability
        WHERE driver_id = ? AND availability_date BETWEEN ? AND ?
        GROUP BY type
    ";
    $stmt = $pdo->prepare($availabilityQuery);
    $stmt->execute([$driverId, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
    $availabilityRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Store counts of day offs and office days
    foreach ($availabilityRecords as $record) {
        $driverAvailabilityData[$driverId][$record['type']] = $record['count'];
    }
}

// Fetch tours for drivers
foreach ($drivers as $driver) {
    $driverId = $driver['Id'];

    // Updated query to fetch tours for drivers
    $toursQuery = "
        SELECT tour_name, COUNT(*) AS tour_count
        FROM groups
        WHERE driver_id = ? AND group_date BETWEEN ? AND ?
        GROUP BY tour_name
    ";
    $stmt = $pdo->prepare($toursQuery);
    $stmt->execute([$driverId, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
    $tourRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Store tour counts
    foreach ($tourRecords as $record) {
        if (isset($driverToursData[$driverId][$record['tour_name']])) {
            $driverToursData[$driverId][$record['tour_name']] += $record['tour_count'];
        } else {
            $driverToursData[$driverId][$record['tour_name']] = $record['tour_count'];
        }
    }
}

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<style>
    .light-orange {
    background-color: #ffcc80; /* Light orange */
}

.light-blue {
    background-color: #80d3ff; /* Light blue */
}

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
</style>

<h1>Driver Statistics</h1>

<div class="wrapper-fit-centered form-container">
    <form action="" class="flex-form">
    <input type="date" id="start_date" value="<?php echo $start_date->format('Y-m-d'); ?>" required>&nbsp;
    <input type="date" id="end_date" value="<?php echo $end_date->format('Y-m-d'); ?>" required>&nbsp;
        <button id="update-dates" class="btn">Select Date</button>
    </form>
</div>

<div class="table-container">
    <table class="table font-small-8">
        <thead>
            <tr class="sticky-header">
                <th>Driver Activity</th>
                <?php foreach ($drivers as $driver): ?>
                    <th><?php echo htmlspecialchars($driver['First_Name'] . ' ' . $driver['Last_Name']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr class="light-orange">
                <td>Day Off</td>
                <?php foreach ($drivers as $driver): ?>
                    <td>
                        <?php
                        echo isset($driverAvailabilityData[$driver['Id']]['day_off']) ? $driverAvailabilityData[$driver['Id']]['day_off'] : 0;
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <tr class="light-blue">
                <td>Office Day</td>
                <?php foreach ($drivers as $driver): ?>
                    <td>
                        <?php
                        echo isset($driverAvailabilityData[$driver['Id']]['office_day']) ? $driverAvailabilityData[$driver['Id']]['office_day'] : 0;
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>

            <?php
        // Collect all unique tour names for drivers to avoid duplicates
        $allDriverTours = [];
        foreach ($driverToursData as $driverId => $tours) {
            foreach ($tours as $tourName => $count) {
                // Only add unique tour names
                if (!in_array($tourName, $allDriverTours)) {
                    $allDriverTours[] = $tourName;
                }
            }
        }

        // Initialize a counter for row classes for drivers
        $rowCount = 0;

        // Render unique tours in the driver's table
        foreach ($allDriverTours as $tourName) {
            $rowClass = ($rowCount % 2 === 0) ? 'even-row' : 'odd-row'; // Determine class
            echo "<tr class='$rowClass'>"; // Set class for the row
            echo "<td>" . htmlspecialchars($tourName) . "</td>"; // Display the tour name
            foreach ($drivers as $driver) {
                echo "<td>";
                // Display the count for the corresponding driver if available
                if (isset($driverToursData[$driver['Id']][$tourName])) {
                    echo $driverToursData[$driver['Id']][$tourName]; // Correctly display the tour count
                } else {
                    echo 0; // No tours for this driver
                }
                echo "</td>";
            }
            echo "</tr>";
            $rowCount++; // Increment row count for next row
        }
        ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('update-dates').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent the form from submitting

        // Get the selected start and end dates
        var startDate = document.getElementById('start_date').value;
        var endDate = document.getElementById('end_date').value;

        // Ensure valid dates
        if (startDate && endDate) {
            // Redirect with new dates as query parameters
            window.location.href = window.location.pathname + "?start_date=" + startDate + "&end_date=" + endDate;
        } else {
            // Optionally add alert or console log for invalid selection
            console.warn('Please select both start and end dates.');
        }
    });
</script>

<?php include('inc/footer.php'); ?>