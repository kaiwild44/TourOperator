<?php
date_default_timezone_set('Asia/Baku');
include('inc/header.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

try {
    // Fetch data from the booking, groups, and tours tables
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
    $groups[$group['group_id']] = $group['max_seats'];
}
foreach ($toursData as $tour) {
    $tours[$tour['tour_name']] = $tour['tour_link'];
}

// Initialize an array to store aggregated bookings by group ID
$aggregatedBookings = [];

// Aggregate booking data by group_id
foreach ($bookingData as $row) {
    $group_id = $row['group_id'];
    
    // Only process bookings where board_display is 1 for outdated entries
    if ($row['board_display'] === 1) {
        // Initialize if not set
        if (!isset($aggregatedBookings[$group_id])) {
            $aggregatedBookings[$group_id] = [
                'tour_name' => $row['tour_name'],
                'tour_date' => $row['tour_date'],
                'russian_count' => 0,
                'english_count' => 0,
                'tour_type' => $row['tour_type'],
                'max_seats' => isset($groups[$group_id]) ? $groups[$group_id] : 0,
            ];
        }

        // Aggregate counts based on lang
        if ($row['lang'] == 'Rus') {
            $aggregatedBookings[$group_id]['russian_count'] += $row['number_of_tourists'];
        } elseif ($row['lang'] == 'Eng') {
            $aggregatedBookings[$group_id]['english_count'] += $row['number_of_tourists'];
        }
    }
}
uasort($aggregatedBookings, function ($a, $b) {
    return strtotime($b['tour_date']) - strtotime($a['tour_date']);
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

<div class="wrapper">
    <h1>Archive</h1>
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
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 0;
                $lastDate = ''; // Variable to track the last date processed

                if (!empty($aggregatedBookings)) {
                    foreach ($aggregatedBookings as $group_id => $data): 
                        $allowedRoles = ['Superadmin', 'Admin', 'Sales_Manager', 'Coordinator'];
                        $showRow = ($data['tour_type'] === 'Group' || in_array($userRole, $allowedRoles));
                        $currentDate = $data['tour_date']; // Store current tour date

                        // Check if the current date is different from the last date
                        if ($lastDate !== $currentDate) {
                            // Add an empty row to separate dates
                            if ($lastDate !== '') { // Ensure not to add an empty row before the first booking
                                echo '<tr><td colspan="7"></td></tr>'; 
                            }
                            $lastDate = $currentDate; // Update the last processed date
                        }

                        $rowClass = ($count % 2 === 0) ? 'even-row' : 'odd-row';
                        $count++;
                        $totalTourists = $data['russian_count'] + $data['english_count'];
                        $guideSeats = ($data['russian_count'] > 0 ? 1 : 0) + ($data['english_count'] > 0 ? 1 : 0);
                        $availableSeats = isset($data['max_seats']) ? max($data['max_seats'] - $totalTourists - $guideSeats, 0) : 'N/A';
                        // Improved start time display from groups table
                        $startTimeDisplay = '';
                        if (isset($groupStartTimes[$group_id]) && $groupStartTimes[$group_id]['time_display'] == 1 && !empty($groupStartTimes[$group_id]['start_time'])) {
                            $startTimeDisplay = '<span class="start-time">(' . htmlspecialchars(date('H:i', strtotime($groupStartTimes[$group_id]['start_time']))) . ')</span>';
                        }
                ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><a href="program.php?group_id=<?php echo urlencode($group_id); ?>">
                                <?php 
                                $originalDate = $data['tour_date'];
                                // Change the format to 'd-m'
                                $formattedDate = date('d-m', strtotime($originalDate));
                                echo htmlspecialchars($formattedDate);
                                ?>
                            </a></td>
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
                        </tr>
                <?php 
                    endforeach;
                } else {
                    echo '<tr><td colspan="7">No bookings available.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('inc/footer.php'); ?>