<?php
include('inc/header.php'); // This includes your db.php and initializes $pdo

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Fetch guides with role 'Guide'
try {
    $guidesQuery = "SELECT Id, First_Name, Last_Name FROM users WHERE Role = 'Guide'";
    $guidesResult = $pdo->query($guidesQuery);
    $guides = $guidesResult->fetchAll();

    // Specify the date range you want to display (current month)
    $start_date = new DateTime('first day of this month');
    $end_date = new DateTime('last day of this month');
    $end_date->modify('+1 day');

    // Fetch group assignments for the specified date range
    $groupsQuery = "
        SELECT guide_id, second_guide_id, group_date, tour_name, guide_lang, second_guide_lang 
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
        
        // Handle primary guide
        $guideId = $row['guide_id'];
        if (!empty($row['guide_lang'])) {
            $groupsData[$date][$guideId] = $row['tour_name'] . ' (' . $row['guide_lang'] . ')';
        } else {
            $groupsData[$date][$guideId] = $row['tour_name'];
        }
    
        // Handle second guide
        $secondGuideId = $row['second_guide_id'];
        if (!empty($secondGuideId)) {
            if (!empty($row['second_guide_lang'])) {
                $groupsData[$date][$secondGuideId] = $row['tour_name'] . ' (' . $row['second_guide_lang'] . ')';
            } else {
                $groupsData[$date][$secondGuideId] = $row['tour_name'];
            }
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Handle form submission for day-off or office day
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guideId = $_POST['guide_id'];
    $availabilityDate = $_POST['availability_date'];
    $type = $_POST['type']; // 'day_off' or 'office_day'

    // Check for conflicts
    $conflictQuery = "
        SELECT COUNT(*) FROM groups 
        WHERE guide_id = ? AND group_date = ?
    ";
    $conflictStmt = $pdo->prepare($conflictQuery);
    $conflictStmt->execute([$guideId, $availabilityDate]);
    $conflictCount = $conflictStmt->fetchColumn();

    if ($conflictCount > 0) {
        $errorMessage = "Conflict: The guide is already assigned to a tour on that date.";
    } else {
        // No conflict; proceed to insert
        $insertQuery = "
            INSERT INTO guide_availability (guide_id, availability_date, type) 
            VALUES (?, ?, ?)
        ";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$guideId, $availabilityDate, $type]);

        // Display success message
        $successMessage = "Successfully added $type for the selected guide.";
    }
}

// Fetch guide availability for the specified date range always
$availabilityQuery = "
    SELECT guide_id, availability_date, type 
    FROM guide_availability 
    WHERE availability_date BETWEEN ? AND ?
";
$availabilityStmt = $pdo->prepare($availabilityQuery);
$availabilityStmt->execute([$startDateFormatted, $endDateFormatted]);
$availabilityResult = $availabilityStmt->fetchAll(PDO::FETCH_ASSOC);

$availabilityData = [];
foreach ($availabilityResult as $row) {
    $availabilityData[$row['availability_date']][$row['guide_id']] = $row['type'];
}

// Combining data for display
$combinedData = [];
$interval = DateInterval::createFromDateString('1 day');
$dateRange = new DatePeriod($start_date, $interval, $end_date);
foreach ($dateRange as $date) {
    $dateStr = $date->format('Y-m-d');
    foreach ($guides as $guide) {
        $guideId = $guide['Id'];
        $combinedData[$dateStr][$guideId] = [
            'tour' => isset($groupsData[$dateStr][$guideId]) ? $groupsData[$dateStr][$guideId] : '',
            'availability' => isset($availabilityData[$dateStr][$guideId]) ? $availabilityData[$dateStr][$guideId] : ''
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

<h1>Guides' Schedule</h1><br>

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
            <label for="guide_id">Select Guide:</label>
            <select name="guide_id" id="guide_id" required>
                <option value="" required>Select One</option>
                <?php foreach ($guides as $guide): ?>
                    <option value="<?php echo $guide['Id']; ?>">
                        <?php echo htmlspecialchars($guide['First_Name'] . ' ' . $guide['Last_Name']); ?>
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
                <?php foreach ($guides as $guide): ?>
                    <th><?php echo htmlspecialchars($guide['First_Name'] . ' ' . $guide['Last_Name']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody id="table-data">
            <?php
            $count = 0; // Counter for alternating row classes
        
            foreach ($dateRange as $date):
                $dateStr = $date->format('Y-m-d');
                $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row'; // Determine the row class
                $count++; // Increment counter
            ?>
                <tr class="<?php echo $rowClass; ?>">
                    <td><?php echo $date->format('j'); ?></td>
                    <?php foreach ($guides as $guide): 
                        $guideId = $guide['Id'];
                        $tourName = htmlspecialchars($combinedData[$dateStr][$guideId]['tour']);
                        $availabilityStatus = htmlspecialchars($combinedData[$dateStr][$guideId]['availability']);
                        
                        // Determine the class for availability
                        $availabilityClass = '';
                        if ($availabilityStatus === 'day_off') {
                            $availabilityClass = 'light-orange'; 
                        } elseif ($availabilityStatus === 'office_day') {
                            $availabilityClass = 'light-blue'; 
                        }
                    ?>
                        <td class="<?php echo $availabilityClass; ?>">
                            <?php 
                            if ($availabilityStatus) {
                                $userFriendlyStatus = ucfirst(str_replace('_', ' ', $availabilityStatus)); 
                                echo "<span class='availability'>$userFriendlyStatus</span><br>";
                            }
                            echo $tourName; 
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include('inc/footer.php'); ?>