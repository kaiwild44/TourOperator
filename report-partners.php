<?php
include('inc/header.php');

// Check if the user is not Superadmin or Admin or Sales_Manager
if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Set default values for start and end dates if not provided
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// Adjust the SQL statement to select only users with the 'Partner' role and bookings within the specified date range if provided
$sql = "
    SELECT 
        u.Id, 
        u.First_Name, 
        u.Last_Name, 
        COUNT(b.id) AS TotalBookings, 
        SUM(b.price) AS TotalRevenue,
        SUM(b.adult_food + b.child_food + b.adult_tickets + b.child_tickets) AS TotalExpenses
    FROM users u
    LEFT JOIN booking b ON u.Id = b.agent_id
    WHERE u.Role = 'Partner'";

// Add date filtering condition if start and end dates are provided
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND DATE(CONVERT_TZ(b.booking_date, '+00:00', '+04:00')) BETWEEN :start_date AND :end_date";
}

// Add name filtering condition if agent_id is provided
if (!empty($_POST['agent_id'])) {
    $sql .= " AND u.Id = :agent_id";
}

// Group by user ID
$sql .= " GROUP BY u.Id";

// Prepare and execute the query
$stmt = $pdo->prepare($sql);

// Bind parameters if start and end dates are provided
if (!empty($startDate) && !empty($endDate)) {
    $stmt->bindParam(':start_date', $startDate);
    $stmt->bindParam(':end_date', $endDate);
}

// Bind agent_id parameter if provided
if (!empty($_POST['agent_id'])) {
    $stmt->bindParam(':agent_id', $_POST['agent_id']);
}

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalBookings = 0;
$totalRevenue = 0;
?>

<div class="wrapper-fit-centered">
    <div class="table-container">
        <!-- Date filtering form -->
        <form id="filter-form" method="post" action="" style="display:flex;flex-direction:column;gap:10px">
            <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>" style="padding:10px 20px;width:100%">

            <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>" style="padding:10px 20px;width:100%">

            <!-- Select menu for filtering by name -->
            <select name="agent_id" id="agent_id" style="padding:10px 20px;width:100%">
                <option value="">Select Name</option>
                <?php foreach ($results as $row): ?>
                    <?php $selected = (isset($_POST['agent_id']) && $_POST['agent_id'] == $row['Id']) ? 'selected' : ''; ?>
                    <option value="<?= $row['Id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($row['First_Name'] . " " . $row['Last_Name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="display:flex; gap:10px;margin-bottom:10px">
                <button type="submit" class="btn">Filter</button>
                <button type="button" class="btn" onclick="clearFilters()">Clear Filters</button>
            </div>
        </form>
        <!-- Display filtered results -->
        <table class="table font-small-8">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Agent</th>
                    <th>Sales:</th>
                    <th>Total:</th>
                    <th>Net Price:</th> <!-- New column header for net price -->
                </tr>
            </thead>
            <tbody>
                <?php $sn = 1; ?>
                <?php foreach ($results as $row): ?>
                    <?php
                        // Add to totals
                        $totalBookings += $row['TotalBookings'];
                        $totalRevenue += $row['TotalRevenue'];

                        // Calculate net price
                        $netPrice = $row['TotalRevenue'] - $row['TotalExpenses'];
                    ?>
                    <tr class="<?= ($sn % 2 == 0) ? 'even-row' : 'odd-row'; ?>">
                        <td><?= $sn++; ?></td>
                        <td><?= htmlspecialchars($row['First_Name'] . " " . $row['Last_Name']) ?></td>
                        <td><?= htmlspecialchars($row['TotalBookings']) ?></td>
                        <td><?= htmlspecialchars(number_format($row['TotalRevenue'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($netPrice, 2)) ?></td> <!-- Display net price -->
                    </tr>
                <?php endforeach; ?>
                <!-- Display totals -->
                <tr class="total-row">
                    <td colspan="2"><b>Total</b></td>
                    <td><b><?= htmlspecialchars($totalBookings) ?></b></td>
                    <td><b><?= htmlspecialchars(number_format($totalRevenue, 2)) ?></b></td>
                    <td><b><?= htmlspecialchars(number_format($totalRevenue - array_sum(array_column($results, 'TotalExpenses')), 2)) ?></b></td> <!-- Total net price -->
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include('inc/footer.php'); ?>

<script>
    function clearFilters() {
        document.getElementById('start_date').value = '';
        document.getElementById('end_date').value = '';
        document.getElementById('agent_id').value = '';
        document.getElementById('filter-form').submit();
    }
</script>