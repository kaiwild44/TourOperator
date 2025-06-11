<?php
include('inc/header.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Handle the form submission for adding new tours
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tour_code = $_POST['tour_code'];
    $tour_name = $_POST['tour_name'];
    $tour_link = $_POST['tour_link'];
    $tickets_expense = $_POST['tickets_expense'];
    $food_expense = $_POST['food_expense'];
    $child_tickets_expense = $_POST['child_tickets_expense']; // New field
    $child_food_expense = $_POST['child_food_expense']; // New field

    try {
        // Insert the new tour into the tours table
        $sqlInsert = "INSERT INTO tours (tour_code, tour_name, tour_link, tickets_expense, food_expense, child_tickets_expense, child_food_expense) 
                      VALUES (:tour_code, :tour_name, :tour_link, :tickets_expense, :food_expense, :child_tickets_expense, :child_food_expense)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'tour_code' => $tour_code,
            'tour_name' => $tour_name,
            'tour_link' => $tour_link,
            'tickets_expense' => $tickets_expense,
            'food_expense' => $food_expense,
            'child_tickets_expense' => $child_tickets_expense, // New field
            'child_food_expense' => $child_food_expense, // New field
        ]);

        echo "New tour added successfully!";
    } catch (PDOException $e) {
        echo "Error adding new tour: " . $e->getMessage();
    }
}

try {
    // Fetch the data from the tours table
    $toursData = fetchToursData($pdo);
} catch (PDOException $e) {
    echo "Error fetching tours data: " . $e->getMessage();
    exit;
}

?>
<div class="wrapper">
    <h1>Add a New Tour</h1>
    <form method="POST" action="tours.php">
        <label for="tour_code">Tour Code:</label>
        <input type="text" id="tour_code" name="tour_code" required><br>

        <label for="tour_name">Tour Name:</label>
        <input type="text" id="tour_name" name="tour_name" required><br>

        <label for="tour_link">Tour Link:</label>
        <input type="url" id="tour_link" name="tour_link" required><br>

        <label for="tickets_expense">Adult Tickets Expense:</label>
        <input type="number" step="0.01" id="tickets_expense" name="tickets_expense" required><br>

        <label for="food_expense">Adult Food Expense:</label>
        <input type="number" step="0.01" id="food_expense" name="food_expense" required><br>

        <label for="child_tickets_expense">Child Tickets Expense:</label>
        <input type="number" step="0.01" id="child_tickets_expense" name="child_tickets_expense" required><br>

        <label for="child_food_expense">Child Food Expense:</label>
        <input type="number" step="0.01" id="child_food_expense" name="child_food_expense" required><br>

        <input type="submit" value="Add Tour" class="btn">
    </form>

    <h1>Existing Tours</h1>
    <table class="table">
    <thead>
        <tr class="sticky-header">
            <th>ID</th>
            <th>Code</th>
            <th>Tour Name</th>
            <th>Tour Link</th>
            <th>Adult Tickets</th>
            <th>Adult Food</th>
            <th>Child Tickets</th>
            <th>Child Food</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $index = 1; // Start numbering from 1
        foreach ($toursData as $row): 
            // Determine if the current index is odd or even
            $rowClass = ($index % 2 === 0) ? 'even-row' : 'odd-row'; 
        ?>
        <tr class="<?php echo $rowClass; ?>">
            <td><?php echo $index; ?></td>
            <td><?php echo htmlspecialchars($row['tour_code']); ?></td>
            <td><?php echo htmlspecialchars($row['tour_name']); ?></td>
            <td><a href="<?php echo htmlspecialchars($row['tour_link']); ?>" target="_blank"><?php echo htmlspecialchars($row['tour_link']); ?></a></td>
            <td><?php echo htmlspecialchars($row['tickets_expense']); ?></td>
            <td><?php echo htmlspecialchars($row['food_expense']); ?></td>
            <td><?php echo htmlspecialchars($row['child_tickets_expense']); ?></td>
            <td><?php echo htmlspecialchars($row['child_food_expense']); ?></td>
            <td><a href="tours-edit.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn">Edit</a></td>
        </tr>
        <?php 
            $index++; // Increment index for next row
        endforeach; 
        ?>
    </tbody>
</table>
</div>

<?php include('inc/footer.php'); ?>
