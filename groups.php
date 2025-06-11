<?php
include('inc/header.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

try {
    // Fetch data for groups, including counts for Russian and English speakers, tour name, and total people excluding infants
    $sql = "
        SELECT g.group_id, g.group_date, g.max_seats,
               t.tour_name,
               SUM(CASE WHEN b.lang = 'Rus' THEN b.adult_no + b.child_no ELSE 0 END) AS russian_count,
               SUM(CASE WHEN b.lang = 'Eng' THEN b.adult_no + b.child_no ELSE 0 END) AS english_count,
               SUM(b.adult_no + b.child_no) AS total_people  -- Total people excluding infants
        FROM groups g
        LEFT JOIN booking b ON g.group_id = b.group_id
        LEFT JOIN tours t ON b.tour_name = t.tour_name
        GROUP BY g.group_id, g.group_date, g.max_seats, t.tour_name
        ORDER BY g.group_date DESC"; // Sort by group date in descending order
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $groupsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching groups data: " . $e->getMessage();
    exit;
}
?>

<h1>Groups</h1>

<!-- Button to Remove Empty Groups -->
<div class="wrapper-fit-centered" style="margin-bottom: 20px;">
    <form action="remove_empty_groups.php" method="POST">
        <button type="submit" class="btn" onclick="return confirm('Are you sure you want to remove all empty groups?');">Remove Empty Groups</button>
    </form>
</div>

<div class="wrapper-fit-centered">
    <table class="table">
        <thead>
            <tr>
                <th>Group ID</th>
                <th>Tour Name</th>
                <th>Group Date</th>
                <th>Max. Seats</th>
                <th>Rus</th>
                <th>Eng</th>
                <th>Status</th> <!-- Status column -->
            </tr>
        </thead>
        <tbody>
            <?php 
            // Initialize variable to store the current month and year for grouping
            $currentMonth = '';
            $currentYear = '';

            foreach ($groupsData as $row): 
                // Extract month and year from group_date
                $groupDate = new DateTime($row['group_date']);
                $month = $groupDate->format('F'); // Full month name
                $year = $groupDate->format('Y');   // Year

                // Check if we are still in the same month/year
                if ($currentMonth !== $month || $currentYear !== $year) {
                    // If the month/year has changed, output a header for that month/year
                    if ($currentMonth !== '' && $currentYear !== '') {
                        // Insert an empty row for spacing before the next month header
                        echo '<tr><td colspan="8" style="height: 10px;"></td></tr>';
                    }
                    echo "<tr><td colspan='8' style='font-weight:bold; font-size: 1.2em;'>$month, $year</td></tr>";
                    $currentMonth = $month;
                    $currentYear = $year;
                }
            ?>
            <tr>
                <td>
                    <a href="program.php?group_id=<?php echo htmlspecialchars($row['group_id']); ?>">
                        <?php echo htmlspecialchars($row['group_id']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($row['tour_name']); ?></td>
                <td><?php echo htmlspecialchars($row['group_date']); ?></td>
                <td><?php echo htmlspecialchars($row['max_seats']); ?></td>
                <td><?php echo htmlspecialchars($row['russian_count']); ?></td>
                <td><?php echo htmlspecialchars($row['english_count']); ?></td>
                <td>
                    <?php
                    // Determine status based on Russian or English speakers
                    if ($row['russian_count'] == 0 && $row['english_count'] == 0) {
                        echo "<span style='color:red;'>Empty</span>"; // Indicates an empty group
                    } else {
                        echo "<span style='color:green;'>Occupied</span>"; // Indicates the group is occupied
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<style>
    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    .table th, .table td {
        border: 1px solid #dddddd;
        text-align: left;
        padding: 8px;
    }
    .table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    .table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .table tr:hover {
        background-color: #f1f1f1;
    }
</style>

<?php include('inc/footer.php'); ?>