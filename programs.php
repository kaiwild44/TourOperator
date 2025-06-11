<?php
include('inc/header.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

try {
    // Fetch data from the booking and groups tables
    $bookingData = fetchBookingData($pdo);
    $groupsData = fetchGroupsData($pdo);
} catch (PDOException $e) {
    echo "Error fetching data: " . $e->getMessage();
    exit;
}

// Create an associative array to map group_id to max_seats
$groups = [];
foreach ($groupsData as $group) {
    $groups[$group['group_id']] = $group['max_seats'];
}

// Initialize an array to store aggregated programs by group ID
$aggregatedPrograms = [];

// Aggregate booking data by group_id
foreach ($bookingData as $row) {
    $group_id = $row['group_id'];
    // Initialize if not set
    if (!isset($aggregatedPrograms[$group_id])) {
        $aggregatedPrograms[$group_id] = [
            'tour_name' => $row['tour_name'],
            'tour_date' => $row['tour_date'],
            'russian_count' => 0,
            'english_count' => 0,
            'max_seats' => $groups[$group_id] ?? 'N/A',
        ];
    }
    
    // Aggregate counts based on lang (removed the space)
    if ($row['lang'] == 'Rus') {
        $aggregatedPrograms[$group_id]['russian_count'] += $row['number_of_tourists'];
    } elseif ($row['lang'] == 'Eng') {
        $aggregatedPrograms[$group_id]['english_count'] += $row['number_of_tourists'];
    }
}
?>
    <h1>All Programs</h1>

    <table>
        <thead>
            <tr>
                <th>Group ID</th> <!-- New Column for Group ID -->
                <th>Tour Name</th>
                <th>Tour Date</th>
                <th>Russian Tourists</th>
                <th>English Tourists</th>
                <th>Max Seats</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Ensure that aggregatedPrograms is not empty
            if (!empty($aggregatedPrograms)) {
                foreach ($aggregatedPrograms as $group_id => $data): ?>
                <tr>
                    <td><a href="program.php?group_id=<?php echo urlencode($group_id); ?>">
                        <?php echo htmlspecialchars($group_id); ?>
                    </a></td>
                    <td><?php echo htmlspecialchars($data['tour_name']); ?></td>
                    <td><?php echo htmlspecialchars($data['tour_date']); ?></td>
                    <td><?php echo htmlspecialchars($data['russian_count']); ?></td>
                    <td><?php echo htmlspecialchars($data['english_count']); ?></td>
                    <td><?php echo htmlspecialchars($data['max_seats']); ?></td>
                </tr>
                <?php endforeach;
            } else {
                echo '<tr><td colspan="6">No programs available.</td></tr>';
            }
            ?>
        </tbody>
    </table>

<?php include('inc/footer.php'); ?>