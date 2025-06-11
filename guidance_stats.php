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
    $guidesQuery = "SELECT Id, First_Name, Last_Name FROM users WHERE Role = 'Guide'";
    $guidesResult = $pdo->query($guidesQuery);
    $guides = $guidesResult->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare arrays to hold the data
    $availabilityData = [];
    $toursData = [];

    // Fetch availability data for the month
    foreach ($guides as $guide) {
        $guideId = $guide['Id'];
        
        $availabilityQuery = "
            SELECT type, COUNT(*) AS count
            FROM guide_availability
            WHERE guide_id = ? AND availability_date BETWEEN ? AND ?
            GROUP BY type
        ";
        $stmt = $pdo->prepare($availabilityQuery);
        $stmt->execute([$guideId, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
        $availabilityRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Store counts of day offs and office days
        foreach ($availabilityRecords as $record) {
            $availabilityData[$guideId][$record['type']] = $record['count'];
        }
    }

// Fetch tours for both guides
foreach ($guides as $guide) {
    $guideId = $guide['Id'];

    // Updated query to fetch tours for both primary and second guides
    $toursQuery = "
        SELECT tour_name, COUNT(*) AS tour_count
        FROM groups
        WHERE (guide_id = ? OR second_guide_id = ?) AND group_date BETWEEN ? AND ?
        GROUP BY tour_name
    ";
    $stmt = $pdo->prepare($toursQuery);
    $stmt->execute([$guideId, $guideId, $start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
    $tourRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Store tour counts
    foreach ($tourRecords as $record) {
        if (isset($toursData[$guideId][$record['tour_name']])) {
            $toursData[$guideId][$record['tour_name']] += $record['tour_count']; 
        } else {
            $toursData[$guideId][$record['tour_name']] = $record['tour_count'];
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

<h1>Guidance Statistics</h1>

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
                <th>Guide Activity</th>
                <?php foreach ($guides as $guide): ?>
                    <th><?php echo htmlspecialchars($guide['First_Name'] . ' ' . $guide['Last_Name']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr class="light-orange"> <!-- Changed to light orange class -->
                <td>Day Off</td>
                <?php foreach ($guides as $guide): ?>
                    <td>
                        <?php
                        echo isset($availabilityData[$guide['Id']]['day_off']) ? $availabilityData[$guide['Id']]['day_off'] : 0;
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>
            <tr class="light-blue"> <!-- Changed to light blue class -->
                <td>Office Day</td>
                <?php foreach ($guides as $guide): ?>
                    <td>
                        <?php
                        echo isset($availabilityData[$guide['Id']]['office_day']) ? $availabilityData[$guide['Id']]['office_day'] : 0;
                        ?>
                    </td>
                <?php endforeach; ?>
            </tr>

            <?php
            // Collect all unique tour names to avoid duplicates
            $allTours = [];
            foreach ($toursData as $guideId => $tours) {
                foreach ($tours as $tourName => $count) {
                    // Only add unique tour names
                    if (!in_array($tourName, $allTours)) {
                        $allTours[] = $tourName;
                    }
                }
            }

            // Initialize a counter for row classes
            $rowCount = 0;

            // Render unique tours in the table
            foreach ($allTours as $tourName) {
                $rowClass = ($rowCount % 2 === 0) ? 'even-row' : 'odd-row'; // Determine class
                
                echo "<tr class='$rowClass'>"; // Set class for the row
                echo "<td>" . htmlspecialchars($tourName) . "</td>"; // Display the tour name
                foreach ($guides as $guide) {
                    echo "<td>";
                    // Display the count for the corresponding guide if available
                    if (isset($toursData[$guide['Id']][$tourName])) {
                        echo $toursData[$guide['Id']][$tourName]; // Correctly display the tour count
                    } else {
                        echo 0; // No tours for this guide
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