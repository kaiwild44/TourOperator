<?php
date_default_timezone_set('Asia/Baku');

include('inc/header.php');

$adminRoles = ['Superadmin', 'Admin', 'Sales_Manager',  'Online_Manager', 'Coordinator'];

// Function to hide outdated entries by setting board_display to 1
function hideOutdatedEntries($pdo) {
    $todayDate = date('Y-m-d');

    try {
        // Update board_display for outdated entries
        $sql = "UPDATE groups SET board_display = 1 WHERE group_date < :todayDate";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['todayDate' => $todayDate]);
        
        // Debug: Check how many rows were affected
        // $rowsAffected = $stmt->rowCount();
        // echo "Rows updated: " . $rowsAffected;
    } catch (PDOException $e) {
        echo "Error updating outdated entries: " . $e->getMessage();
        exit;
    }
}

// Call the function to hide outdated entries
hideOutdatedEntries($pdo);

// Initialize date variables for filtering
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';

try {
    // Fetch data from the booking and groups tables
    $bookingData = fetchBookingData($pdo);
    $groupsData = fetchGroupsData($pdo);

    // Fetch the tour links:
    $tourQuery = "SELECT tour_name, tour_link FROM tours";
    $stmt = $pdo->prepare($tourQuery);
    $stmt->execute();
    $toursData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch group start times. This is added to avoid redundant queries within the loop.
    $groupStartTimes = [];
    $stmt = $pdo->prepare("SELECT group_id, start_time, time_display FROM groups");
    $stmt->execute();
    $groupStartTimesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($groupStartTimesData as $groupStartTime){
        $groupStartTimes[$groupStartTime['group_id']] = [
            'start_time' => $groupStartTime['start_time'],
            'time_display' => $groupStartTime['time_display']
        ];
    }

} catch (PDOException $e) {
    echo "Error fetching data: " . $e->getMessage();
    exit;
}

// Create associative arrays
$groups = [];
$tours = [];
foreach ($groupsData as $group) {
    // Always store max_seats regardless of board_display
    $groups[$group['group_id']] = $group['max_seats'];
}
foreach ($toursData as $tour) {
    $tours[$tour['tour_name']] = $tour['tour_link'];
}

// Initialize an array to store aggregated bookings by group ID
$aggregatedBookings = [];

foreach ($bookingData as $row) {
    $group_id = $row['group_id'];

    // Only process bookings where board_display is 0. Assuming $row comes from a JOIN query
    // that includes the 'board_display' column from the 'groups' table.
    if ($row['board_display'] === 0) {
        // Initialize if not set
        if (!isset($aggregatedBookings[$group_id])) {
            $aggregatedBookings[$group_id] = [
                'tour_name' => $row['tour_name'],
                'tour_date' => $row['tour_date'],
                'russian_count' => 0,
                'english_count' => 0,
                'tour_type' => $row['tour_type'],
                'max_seats' => isset($groups[$group_id]) ? $groups[$group_id] : 0,
                //'board_display' => $row['board_display'], // No longer needed here
            ];
        }

        // Aggregate counts based on language
        if ($row['lang'] == 'Rus') {
            $aggregatedBookings[$group_id]['russian_count'] += $row['adult_no'] + $row['child_no'];
        } elseif ($row['lang'] == 'Eng') {
            $aggregatedBookings[$group_id]['english_count'] += $row['adult_no'] + $row['child_no'];
        }
    }
}

// Ensure to calculate the total for each group to ensure values are reflecting the latest updates
foreach ($aggregatedBookings as $group_id => $data) {
    $data['total_tourists'] = $data['russian_count'] + $data['english_count'];
    $aggregatedBookings[$group_id] = $data;
}

// Apply the date filter on aggregated bookings if the dates are provided
if (!empty($startDate) && !empty($endDate)) {
    $aggregatedBookings = array_filter($aggregatedBookings, function($data) use ($startDate, $endDate) {
        return ($data['tour_date'] >= $startDate && $data['tour_date'] <= $endDate);
    });
}

// Initialize todayDate variable to today's date
$todayDate = date('Y-m-d');

// Filter upcoming bookings
$upcomingBookings = array_filter($aggregatedBookings, function($data) use ($todayDate) {
    return $data['tour_date'] >= $todayDate;
});

// Sort the filtered bookings by tour_date in ascending order
usort($upcomingBookings, function($a, $b) {
    return strtotime($a['tour_date']) - strtotime($b['tour_date']);
});
?>


<style>
    .start-time {
    margin-left: 8px;
    color: red;
    font-weight: bold;
    }
    .tour-type-private {
        background-color: #333;
        color: #fff;
        font-weight: bold;
    }

    .red-text {
        color: red;
        font-weight: bold;
    }

    .green-text {
        color: green;
        font-weight: bold;
    }
</style>

<h1>Board</h1>


<!-- Date Filter Form -->
<?php if (in_array($userRole, $adminRoles)) { ?>
<div class="side-by-side">
    <form method="POST" action="">
        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
        <input type="submit" value="Filter" class="btn">
    </form>
</div>
<br>
<?php } ?>

<div class="wrapper">

    <div class="table-container">
    <table class="table">
        <thead>
            <tr class="sticky-header">
                <th>Date</th>
                <th>Tour</th>
                <th>Tp</th>
                <th>Ru</th>
                <th>En</th>
                <th><i class="fa fa-chair"></i></th>
                <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager') { ?>
                   <th><i class="fa fa-trash"></i></th>
                <?php } ?>
            </tr>
        </thead>
            <tbody>
                <?php 
                    $count = 0;
                    $lastDate = '';
                    $todayDate = date('Y-m-d');
                    $nextTwoDays = new DateTime($todayDate);
                    $nextTwoDays->modify('+2 days');
                    $endDate = $nextTwoDays->format('Y-m-d');

                    if (!empty($aggregatedBookings)) {
                        foreach ($aggregatedBookings as $group_id => $data):
                            $allowedRoles = ['Superadmin', 'Admin', 'Sales_Manager', 'Online_Manager', 'Coordinator'];
                            $showRow = ($data['tour_type'] === 'Group' || in_array($userRole, $allowedRoles));

                            // Condition to determine if the booking should be shown
                            $isAdmin = in_array($userRole, $adminRoles);

                            // If the user is NOT an admin, show only current date and the next three days
                            if (!$isAdmin) {
                                if ($data['tour_date'] < $todayDate || $data['tour_date'] > $endDate) {
                                    continue; // Skip this entry if it's not within the next three days
                                }
                            } 

                            // Existing logic for displaying the row
                            if (($isAdmin || ($data['tour_date'] >= $todayDate && $data['tour_date'] <= $endDate)) && ($data['tour_type'] === 'Group' || $isAdmin)) {
                                $currentDate = $data['tour_date'];

                                // Check if the current date is different from the last date
                                if ($lastDate !== $currentDate) {
                                    // Update lastDate to current date before outputting the next row
                                    if ($lastDate !== '') { // Ensure not to add an empty row before the first
                                        // Add an empty row to separate dates
                                        echo '<tr class="bolder-line"><td colspan="7"></td></tr>'; 
                                    }
                                    $lastDate = $currentDate; // Update the last processed date
                                }

                                $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row';
                                $count++;
                                // Calculate available seats based on max_seats independent of time_display logic
                                $totalTourists = $data['russian_count'] + $data['english_count'];
                                $guideSeats = ($data['russian_count'] > 0 ? 1 : 0) + ($data['english_count'] > 0 ? 1 : 0);
                                
                                // Only calculate availableSeats based on max_seats
                                $availableSeats = isset($data['max_seats']) ? max($data['max_seats'] - $totalTourists - $guideSeats, 0) : 'N/A';
                                
                                //Improved start time display from groups table
                                $startTimeDisplay = '';
                                if (isset($groupStartTimes[$group_id]) && $groupStartTimes[$group_id]['time_display'] == 1 && !empty($groupStartTimes[$group_id]['start_time'])) {
                                    $startTimeDisplay = '<span class="start-time">(' . htmlspecialchars(date('H:i', strtotime($groupStartTimes[$group_id]['start_time']))) . ')</span>';
                                }
                ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td>
                            <?php 
                            // Assuming you have the necessary role stored in a variable called $userRole
                            $originalDate = $data['tour_date']; // Change according to your context
                            // Change the format from 'm-d' to 'd-m'
                            $formattedDate = date('d-m', strtotime($originalDate));

                            // Check if the user role is Superadmin, Admin, or Coordinator
                            if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Coordinator' || $userRole === 'Sales_Manager' || $userRole === 'Online_Manager') {
                                // Display as hyperlink
                                echo '<a href="program.php?group_id=' . urlencode($group_id) . '">' . htmlspecialchars($formattedDate) . '</a>';
                            } else {
                                // Display as plain text
                                echo htmlspecialchars($formattedDate);
                            }
                            ?>
                        </td>
                        <td>
                            <?php 
                            $tour_link = isset($tours[$data['tour_name']]) ? $tours[$data['tour_name']] : '#';
                            ?>
                            <a href="<?php echo htmlspecialchars($tour_link); ?>">
                                <?php echo htmlspecialchars($data['tour_name']); ?>
                            </a>
                            <?php echo $startTimeDisplay; ?>
                        </td>

                        <?php 
                        $tourTypeClass = $data['tour_type'] === 'Group' ? '' : 'tour-type-private';
                        echo '<td class="' . $tourTypeClass . '">' . ($data['tour_type'] === 'Group' ? 'Gr' : 'Pr') . '</td>';
                        ?>

                        <td><?php echo htmlspecialchars($data['russian_count']); ?></td>
                        <td><?php echo htmlspecialchars($data['english_count']); ?></td>                        
                        <td>
                            <span class="<?php echo $availableSeats > 0 ? 'green-text' : 'red-text'; ?>">
                                <?php echo htmlspecialchars($availableSeats); ?>
                            </span>
                        </td>
                        <?php if ($userRole === 'Superadmin' || $userRole === 'Admin' || $userRole === 'Sales_Manager') { ?>
                            <td>
                                <div id="actions">
                                    <form method="post" action="board_display.php" style="display:inline;">
                                        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                                        <input type="hidden" name="board_display" value="1"> <!-- Indicate the action to hide the entry -->
                                        <button type="submit" class="btn-trash" title="Hide Entry"><i class="fa fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        <?php } ?>
                <?php 
                            }
                        endforeach;
                    } else {
                        echo '<tr><td colspan="7">No bookings available.</td></tr>';
                    }
                ?>
            </tbody>
    </table>
</div>

</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        $('.trash-button').click(function(e) {
            e.preventDefault();
            var groupId = $(this).data('group-id');
            $.ajax({
                url: 'update_group.php',
                type: 'POST',
                data: { group_id: groupId },
                success: function(response) {
                    if (response == "success") {
                        // Remove the row from the table or refresh the page
                        $('tr[data-group-id="' + groupId + '"]').remove();
                    } else {
                        alert("Error: " + response);
                    }
                }
            });
        });
    });
</script>