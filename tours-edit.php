<?php
include('inc/header.php');

if ($_SESSION['role'] !== 'Superadmin' && $_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Sales_Manager') {
    header("Location: access_denied.php");
    exit();
}

// Check if the id parameter is provided
if (!isset($_GET['id'])) {
    echo "No tour ID specified!";
    exit;
}

$id = $_GET['id'];

try {
    // Fetch the existing tour data
    $sqlFetch = "SELECT * FROM tours WHERE id = :id";
    $stmtFetch = $pdo->prepare($sqlFetch);
    $stmtFetch->execute(['id' => $id]);
    $tour = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if (!$tour) {
        echo "Tour not found!";
        exit;
    }
} catch (PDOException $e) {
    echo "Error fetching tour data: " . $e->getMessage();
    exit;
}

// Handle form submission for editing the tour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tour_code = $_POST['tour_code'];
    $tour_name = $_POST['tour_name'];
    $tour_link = $_POST['tour_link'];
    $tickets_expense = $_POST['tickets_expense'];
    $food_expense = $_POST['food_expense'];
    $child_tickets_expense = $_POST['child_tickets_expense']; // New field
    $child_food_expense = $_POST['child_food_expense']; // New field
    $other_expenses = $_POST['other_expenses']; // This can be empty

    try {
        // Update the tour
        $sqlUpdate = "UPDATE tours SET 
                      tour_code = :tour_code, 
                      tour_name = :tour_name, 
                      tour_link = :tour_link,
                      tickets_expense = :tickets_expense, 
                      food_expense = :food_expense,
                      child_tickets_expense = :child_tickets_expense, 
                      child_food_expense = :child_food_expense, 
                      other_expenses = :other_expenses
                      WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'tour_code' => $tour_code,
            'tour_name' => $tour_name,
            'tour_link' => $tour_link,
            'tickets_expense' => $tickets_expense,
            'food_expense' => $food_expense,
            'child_tickets_expense' => $child_tickets_expense,
            'child_food_expense' => $child_food_expense,
            'other_expenses' => $other_expenses,
            'id' => $id,
        ]);

        echo "Tour updated successfully!";
        // Redirect back to tours.php if needed
        header("Location: tours.php");
        exit;
    } catch (PDOException $e) {
        echo "Error updating tour: " . $e->getMessage();
    }
}
?>

<div class="wrapper">
<h1>Edit Tour</h1>
    <form method="POST" action="">
        <label for="tour_code">Code:</label>
        <input type="text" id="tour_code" name="tour_code" value="<?php echo htmlspecialchars($tour['tour_code']); ?>" required><br>

        <label for="tour_name">Tour Name:</label>
        <input type="text" id="tour_name" name="tour_name" value="<?php echo htmlspecialchars($tour['tour_name']); ?>" required><br>

        <label for="tour_link">Tour Link:</label>
        <input type="url" id="tour_link" name="tour_link" value="<?php echo htmlspecialchars($tour['tour_link']); ?>" required><br>

        <label for="tickets_expense">Adult Tickets Expense:</label>
        <input type="number" step="0.01" id="tickets_expense" name="tickets_expense" value="<?php echo htmlspecialchars($tour['tickets_expense']); ?>" required><br>

        <label for="food_expense">Adult Food Expense:</label>
        <input type="number" step="0.01" id="food_expense" name="food_expense" value="<?php echo htmlspecialchars($tour['food_expense']); ?>" required><br>

        <label for="child_tickets_expense">Child Tickets Expense:</label>
        <input type="number" step="0.01" id="child_tickets_expense" name="child_tickets_expense" value="<?php echo htmlspecialchars($tour['child_tickets_expense']); ?>"    ><br>
    
        <label for="child_food_expense">Child Food Expense:</label>
        <input type="number" step="0.01" id="child_food_expense" name="child_food_expense" value="<?php echo htmlspecialchars($tour['child_food_expense']); ?>" required><br>

        <label for="other_expenses">Other Expenses:</label>
        <input type="number" step="0.01" id="other_expenses" name="other_expenses" value="<?php echo htmlspecialchars($tour['other_expenses']); ?>"><br>

        <input type="submit" value="Update Tour" class="btn">
    </form>
</div>

<?php include('inc/footer.php'); ?>