<?php
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
    // Initialize if not set
    if (!isset($aggregatedBookings[$group_id])) {
        $aggregatedBookings[$group_id] = [
            'tour_name' => $row['tour_name'],
            'tour_date' => $row['tour_date'],
            'russian_count' => 0,
            'english_count' => 0,
            'tour_type' => $row['tour_type'] // Add tour type to aggregated data
        ];
    }
    
    // Aggregate counts based on lang
    if ($row['lang'] == 'Rus') {
        $aggregatedBookings[$group_id]['russian_count'] += $row['number_of_tourists'];
    } elseif ($row['lang'] == 'Eng') {
        $aggregatedBookings[$group_id]['english_count'] += $row['number_of_tourists'];
    }
}
?>
<div class="wrapper">
    <h1>Board</h1>
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
                        $availableSeats = isset($groups[$group_id]) ? ($groups[$group_id] - $totalTourists - $guideSeats) : 'N/A';
                ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><a href="program.php?group_id=<?php echo urlencode($group_id); ?>">
                                <?php 
                                $originalDate = $data['tour_date'];
                                $formattedDate = date('m-d', strtotime($originalDate));
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
                            </td>
                            <td>
                                <?php 
                                echo $data['tour_type'] === 'Group' ? 'Gr' : 'Pr'; 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($data['russian_count']); ?></td>
                            <td><?php echo htmlspecialchars($data['english_count']); ?></td>                        
                            <td><?php echo htmlspecialchars($availableSeats); ?></td> 
                            <!--<td>-->
                                <!-- Button to redistribute tourists -->
                            <!--    <form method="post" action="redistribute.php">-->
                            <!--        <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">-->
                            <!--        <button type="submit" class="btn"><i class="fa fa-sync-alt"></i></button>-->
                            <!--    </form>-->
                            <!--</td>-->
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